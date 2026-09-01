resource "aws_ecs_cluster" "main" {
  name = "${local.name_prefix}-cluster"

  setting {
    name  = "containerInsights"
    value = "enabled"
  }

  tags = {
    Name = "${local.name_prefix}-cluster"
  }
}

data "aws_iam_policy_document" "ecs_task_assume_role" {
  statement {
    effect = "Allow"

    principals {
      type        = "Service"
      identifiers = ["ecs-tasks.amazonaws.com"]
    }

    actions = [
      "sts:AssumeRole"
    ]
  }
}

resource "aws_iam_role" "ecs_task_execution" {
  name               = "${local.name_prefix}-ecs-execution-role"
  assume_role_policy = data.aws_iam_policy_document.ecs_task_assume_role.json
}

resource "aws_iam_role_policy_attachment" "ecs_task_execution" {
  role = aws_iam_role.ecs_task_execution.name

  policy_arn = "arn:aws:iam::aws:policy/service-role/AmazonECSTaskExecutionRolePolicy"
}

resource "aws_iam_role" "ecs_task" {
  name               = "${local.name_prefix}-ecs-task-role"
  assume_role_policy = data.aws_iam_policy_document.ecs_task_assume_role.json
}

resource "aws_ecs_task_definition" "backend" {
  family                   = "${local.name_prefix}-backend"
  network_mode             = "awsvpc"
  requires_compatibilities = ["FARGATE"]

  cpu    = var.backend_cpu
  memory = var.backend_memory

  execution_role_arn = aws_iam_role.ecs_task_execution.arn
  task_role_arn      = aws_iam_role.ecs_task.arn

  container_definitions = jsonencode([
    {
      name      = "app"
      image     = "${aws_ecr_repository.backend.repository_url}:${var.backend_image_tag}"
      essential = true

      command = [
        "php-fpm",
        "-F"
      ]

      portMappings = [
        {
          containerPort = 9000
          protocol      = "tcp"
        }
      ]

      environment = [
        {
          name  = "APP_ENV"
          value = var.environment
        },
        {
          name  = "APP_DEBUG"
          value = "false"
        },
        {
          name  = "CACHE_STORE"
          value = "redis"
        },
        {
          name  = "QUEUE_CONNECTION"
          value = "database"
        },
        {
          name  = "DB_HOST"
          value = var.database_host
        },
        {
          name  = "DB_PORT"
          value = var.database_port
        },
        {
          name  = "DB_DATABASE"
          value = var.database_name
        },
        {
          name  = "DB_USERNAME"
          value = var.database_username
        },
        {
          name  = "TELEMETRY_DB_HOST"
          value = var.telemetry_database_host
        },
        {
          name  = "TELEMETRY_DB_PORT"
          value = var.telemetry_database_port
        },
        {
          name  = "TELEMETRY_DB_DATABASE"
          value = var.telemetry_database_name
        },
        {
          name  = "TELEMETRY_DB_USERNAME"
          value = var.telemetry_database_username
        },
        {
          name  = "REDIS_HOST"
          value = var.redis_host
        },
        {
          name  = "REDIS_PORT"
          value = var.redis_port
        }
      ]


      secrets = concat(
        var.database_secret_arn != "" ? [
          {
            name      = "DB_PASSWORD"
            valueFrom = "${var.database_secret_arn}:DB_PASSWORD::"
          }
        ] : [],
        var.telemetry_database_secret_arn != "" ? [
          {
            name      = "TELEMETRY_DB_PASSWORD"
            valueFrom = "${var.telemetry_database_secret_arn}:TELEMETRY_DB_PASSWORD::"
          }
        ] : []
      )

      logConfiguration = {
        logDriver = "awslogs"

        options = {
          awslogs-group         = aws_cloudwatch_log_group.api.name
          awslogs-region        = var.aws_region
          awslogs-stream-prefix = "app"
        }
      }
    },
    {
      name      = "nginx"
      image     = "${aws_ecr_repository.nginx.repository_url}:${var.nginx_image_tag}"
      essential = true

      portMappings = [
        {
          containerPort = 80
          protocol      = "tcp"
        }
      ]

      dependsOn = [
        {
          containerName = "app"
          condition     = "START"
        }
      ]


      secrets = concat(
        var.database_secret_arn != "" ? [
          {
            name      = "DB_PASSWORD"
            valueFrom = "${var.database_secret_arn}:DB_PASSWORD::"
          }
        ] : [],
        var.telemetry_database_secret_arn != "" ? [
          {
            name      = "TELEMETRY_DB_PASSWORD"
            valueFrom = "${var.telemetry_database_secret_arn}:TELEMETRY_DB_PASSWORD::"
          }
        ] : []
      )

      logConfiguration = {
        logDriver = "awslogs"

        options = {
          awslogs-group         = aws_cloudwatch_log_group.nginx.name
          awslogs-region        = var.aws_region
          awslogs-stream-prefix = "nginx"
        }
      }
    }
  ])

  tags = {
    Name = "${local.name_prefix}-backend-task"
  }
}

resource "aws_ecs_service" "backend" {
  name            = "${local.name_prefix}-backend"
  cluster         = aws_ecs_cluster.main.id
  task_definition = aws_ecs_task_definition.backend.arn

  desired_count = var.backend_desired_count
  launch_type   = "FARGATE"

  deployment_circuit_breaker {
    enable   = true
    rollback = true
  }

  load_balancer {
    target_group_arn = aws_lb_target_group.backend.arn
    container_name   = "nginx"
    container_port   = 80
  }

  network_configuration {
    subnets = aws_subnet.private_app[*].id

    security_groups = [
      aws_security_group.ecs.id
    ]

    assign_public_ip = false
  }

  deployment_minimum_healthy_percent = 50
  deployment_maximum_percent         = 200

  tags = {
    Name = "${local.name_prefix}-backend-service"
  }
}

resource "aws_ecs_task_definition" "queue_worker" {
  family                   = "${local.name_prefix}-queue-worker"
  network_mode             = "awsvpc"
  requires_compatibilities = ["FARGATE"]

  cpu    = var.queue_worker_cpu
  memory = var.queue_worker_memory

  execution_role_arn = aws_iam_role.ecs_task_execution.arn
  task_role_arn      = aws_iam_role.ecs_task.arn

  container_definitions = jsonencode([
    {
      name      = "queue-worker"
      image     = "${aws_ecr_repository.backend.repository_url}:${var.backend_image_tag}"
      essential = true

      command = [
        "php",
        "artisan",
        "queue:work",
        "--sleep=3",
        "--tries=3",
        "--timeout=60"
      ]


      secrets = concat(
        var.database_secret_arn != "" ? [
          {
            name      = "DB_PASSWORD"
            valueFrom = "${var.database_secret_arn}:DB_PASSWORD::"
          }
        ] : [],
        var.telemetry_database_secret_arn != "" ? [
          {
            name      = "TELEMETRY_DB_PASSWORD"
            valueFrom = "${var.telemetry_database_secret_arn}:TELEMETRY_DB_PASSWORD::"
          }
        ] : []
      )

      logConfiguration = {
        logDriver = "awslogs"

        options = {
          awslogs-group         = aws_cloudwatch_log_group.queue.name
          awslogs-region        = var.aws_region
          awslogs-stream-prefix = "worker"
        }
      }
    }
  ])

  tags = {
    Name = "${local.name_prefix}-queue-worker-task"
  }
}

resource "aws_ecs_service" "queue_worker" {
  name            = "${local.name_prefix}-queue-worker"
  cluster         = aws_ecs_cluster.main.id
  task_definition = aws_ecs_task_definition.queue_worker.arn

  desired_count = var.queue_worker_desired_count
  launch_type   = "FARGATE"

  deployment_circuit_breaker {
    enable   = true
    rollback = true
  }

  network_configuration {
    subnets = aws_subnet.private_app[*].id

    security_groups = [
      aws_security_group.ecs.id
    ]

    assign_public_ip = false
  }

  tags = {
    Name = "${local.name_prefix}-queue-worker-service"
  }
}

resource "aws_ecs_task_definition" "scheduler" {
  family                   = "${local.name_prefix}-scheduler"
  network_mode             = "awsvpc"
  requires_compatibilities = ["FARGATE"]

  cpu    = var.scheduler_cpu
  memory = var.scheduler_memory

  execution_role_arn = aws_iam_role.ecs_task_execution.arn
  task_role_arn      = aws_iam_role.ecs_task.arn

  container_definitions = jsonencode([
    {
      name      = "scheduler"
      image     = "${aws_ecr_repository.backend.repository_url}:${var.backend_image_tag}"
      essential = true

      command = [
        "php",
        "artisan",
        "schedule:work"
      ]

      logConfiguration = {
        logDriver = "awslogs"

        options = {
          awslogs-group         = aws_cloudwatch_log_group.scheduler.name
          awslogs-region        = var.aws_region
          awslogs-stream-prefix = "scheduler"
        }
      }
    }
  ])

  tags = {
    Name = "${local.name_prefix}-scheduler-task"
  }
}

resource "aws_ecs_service" "scheduler" {
  name            = "${local.name_prefix}-scheduler"
  cluster         = aws_ecs_cluster.main.id
  task_definition = aws_ecs_task_definition.scheduler.arn

  desired_count = var.scheduler_desired_count
  launch_type   = "FARGATE"

  deployment_circuit_breaker {
    enable   = true
    rollback = true
  }

  network_configuration {
    subnets = aws_subnet.private_app[*].id

    security_groups = [
      aws_security_group.ecs.id
    ]

    assign_public_ip = false
  }

  tags = {
    Name = "${local.name_prefix}-scheduler-service"
  }
}
