resource "aws_cloudwatch_log_group" "api" {
  name              = "/ecs/${local.name_prefix}/api"
  retention_in_days = 7

  tags = {
    Name = "${local.name_prefix}-api-logs"
  }
}

resource "aws_cloudwatch_log_group" "nginx" {
  name              = "/ecs/${local.name_prefix}/nginx"
  retention_in_days = 7

  tags = {
    Name = "${local.name_prefix}-nginx-logs"
  }
}

resource "aws_cloudwatch_log_group" "queue" {
  name              = "/ecs/${local.name_prefix}/queue"
  retention_in_days = 7

  tags = {
    Name = "${local.name_prefix}-queue-logs"
  }
}

resource "aws_cloudwatch_log_group" "scheduler" {
  name              = "/ecs/${local.name_prefix}/scheduler"
  retention_in_days = 7

  tags = {
    Name = "${local.name_prefix}-scheduler-logs"
  }
}
