variable "aws_region" {
  description = "AWS region for the deployment."
  type        = string
  default     = "ap-south-1"
}

variable "environment" {
  description = "Deployment environment."
  type        = string
  default     = "staging"

  validation {
    condition     = contains(["staging", "production"], var.environment)
    error_message = "Environment must be staging or production."
  }
}

variable "project_name" {
  description = "Project name used for AWS resource naming."
  type        = string
  default     = "solar-energy-monitoring-platform"
}

variable "vpc_cidr" {
  description = "CIDR range for the VPC."
  type        = string
  default     = "10.20.0.0/16"
}

variable "availability_zones" {
  description = "Availability Zones for the deployment."
  type        = list(string)

  default = [
    "ap-south-1a",
    "ap-south-1b"
  ]
}
variable "backend_image_tag" {
  description = "Docker image tag deployed to ECS."
  type        = string
  default     = "demo"
}

variable "backend_cpu" {
  description = "CPU units for the Laravel ECS task."
  type        = number
  default     = 512
}

variable "backend_memory" {
  description = "Memory in MiB for the Laravel ECS task."
  type        = number
  default     = 1024
}

variable "backend_desired_count" {
  description = "Number of Laravel API tasks."
  type        = number
  default     = 1
}

variable "nginx_image_tag" {
  description = "Docker image tag deployed for Nginx."
  type        = string
  default     = "demo"
}

variable "database_secret_arn" {
  description = "ARN of the AWS Secrets Manager secret containing the application database credentials."
  type        = string
  default     = ""
}

variable "telemetry_database_secret_arn" {
  description = "ARN of the AWS Secrets Manager secret containing telemetry database credentials."
  type        = string
  default     = ""
}

variable "database_host" {
  description = "Application database hostname."
  type        = string
  default     = ""
}

variable "database_port" {
  description = "Application database port."
  type        = string
  default     = "3306"
}

variable "database_name" {
  description = "Application database name."
  type        = string
  default     = "solar_energy"
}

variable "database_username" {
  description = "Application database username."
  type        = string
  default     = ""
}

variable "telemetry_database_host" {
  description = "Telemetry PostgreSQL hostname."
  type        = string
  default     = ""
}

variable "telemetry_database_port" {
  description = "Telemetry PostgreSQL port."
  type        = string
  default     = "5432"
}

variable "telemetry_database_name" {
  description = "Telemetry PostgreSQL database name."
  type        = string
  default     = "telemetry"
}

variable "telemetry_database_username" {
  description = "Telemetry PostgreSQL username."
  type        = string
  default     = ""
}

variable "redis_host" {
  description = "Redis hostname."
  type        = string
  default     = ""
}

variable "redis_port" {
  description = "Redis port."
  type        = string
  default     = "6379"
}

variable "queue_worker_cpu" {
  description = "CPU units for the Laravel queue worker."
  type        = number
  default     = 256
}

variable "queue_worker_memory" {
  description = "Memory in MiB for the Laravel queue worker."
  type        = number
  default     = 512
}

variable "queue_worker_desired_count" {
  description = "Number of queue worker tasks."
  type        = number
  default     = 1
}

variable "scheduler_cpu" {
  description = "CPU units for the Laravel scheduler."
  type        = number
  default     = 256
}

variable "scheduler_memory" {
  description = "Memory in MiB for the Laravel scheduler."
  type        = number
  default     = 512
}

variable "scheduler_desired_count" {
  description = "Number of scheduler tasks."
  type        = number
  default     = 1
}

variable "backend_deployment_min_healthy_percent" {
  description = "Minimum percentage of healthy backend tasks during deployment."
  type        = number
  default     = 50

  validation {
    condition = (
      var.backend_deployment_min_healthy_percent >= 0 &&
      var.backend_deployment_min_healthy_percent <= 100
    )
    error_message = "backend_deployment_min_healthy_percent must be between 0 and 100."
  }
}

variable "backend_deployment_max_percent" {
  description = "Maximum percentage of backend tasks allowed during deployment."
  type        = number
  default     = 200

  validation {
    condition     = var.backend_deployment_max_percent >= 100
    error_message = "backend_deployment_max_percent must be at least 100."
  }
}

variable "backend_health_check_grace_period_seconds" {
  description = "Grace period before ECS evaluates backend load balancer health."
  type        = number
  default     = 60

  validation {
    condition = (
      var.backend_health_check_grace_period_seconds >= 0 &&
      var.backend_health_check_grace_period_seconds <= 3600
    )
    error_message = "backend_health_check_grace_period_seconds must be between 0 and 3600."
  }
}

variable "backend_scale_in_cooldown_seconds" {
  description = "Backend ECS autoscaling scale-in cooldown."
  type        = number
  default     = 180

  validation {
    condition     = var.backend_scale_in_cooldown_seconds >= 0
    error_message = "backend_scale_in_cooldown_seconds must be non-negative."
  }
}

variable "backend_scale_out_cooldown_seconds" {
  description = "Backend ECS autoscaling scale-out cooldown."
  type        = number
  default     = 60

  validation {
    condition     = var.backend_scale_out_cooldown_seconds >= 0
    error_message = "backend_scale_out_cooldown_seconds must be non-negative."
  }
}

variable "queue_worker_scale_in_cooldown_seconds" {
  description = "Queue worker ECS autoscaling scale-in cooldown."
  type        = number
  default     = 180

  validation {
    condition     = var.queue_worker_scale_in_cooldown_seconds >= 0
    error_message = "queue_worker_scale_in_cooldown_seconds must be non-negative."
  }
}

variable "queue_worker_scale_out_cooldown_seconds" {
  description = "Queue worker ECS autoscaling scale-out cooldown."
  type        = number
  default     = 60

  validation {
    condition     = var.queue_worker_scale_out_cooldown_seconds >= 0
    error_message = "queue_worker_scale_out_cooldown_seconds must be non-negative."
  }
}

variable "backend_min_capacity" {
  description = "Minimum number of API ECS tasks."
  type        = number
  default     = 1
}

variable "backend_max_capacity" {
  description = "Maximum number of API ECS tasks."
  type        = number
  default     = 4
}

variable "backend_cpu_target" {
  description = "Target average CPU utilization for API ECS autoscaling."
  type        = number
  default     = 60
}

variable "queue_worker_min_capacity" {
  description = "Minimum number of queue worker ECS tasks."
  type        = number
  default     = 1
}

variable "queue_worker_max_capacity" {
  description = "Maximum number of queue worker ECS tasks."
  type        = number
  default     = 4
}

variable "queue_worker_cpu_target" {
  description = "Target average CPU utilization for queue worker autoscaling."
  type        = number
  default     = 60
}

variable "mysql_instance_class" {
  description = "RDS MySQL instance class."
  type        = string
  default     = "db.t4g.micro"
}

variable "mysql_allocated_storage" {
  description = "Initial MySQL storage in GiB."
  type        = number
  default     = 20
}

variable "mysql_max_allocated_storage" {
  description = "Maximum MySQL storage in GiB."
  type        = number
  default     = 50
}

variable "postgres_instance_class" {
  description = "RDS PostgreSQL instance class."
  type        = string
  default     = "db.t4g.micro"
}

variable "postgres_allocated_storage" {
  description = "Initial PostgreSQL storage in GiB."
  type        = number
  default     = 20
}

variable "postgres_max_allocated_storage" {
  description = "Maximum PostgreSQL storage in GiB."
  type        = number
  default     = 50
}

variable "redis_node_type" {
  description = "ElastiCache Redis node type."
  type        = string
  default     = "cache.t4g.micro"
}

variable "domain_name" {
  description = "DNS name used for HTTPS."
  type        = string
  default     = ""
}

variable "enable_https" {
  description = "Whether the ALB HTTPS listener should be provisioned."
  type        = bool
  default     = false
}

variable "enable_deletion_protection" {
  description = "Enable deletion protection for production resources."
  type        = bool
  default     = false
}

variable "mysql_multi_az" {
  description = "Enable Multi-AZ deployment for the application MySQL database."
  type        = bool
  default     = false
}

variable "postgres_multi_az" {
  description = "Enable Multi-AZ deployment for the telemetry PostgreSQL database."
  type        = bool
  default     = false
}

variable "mysql_backup_retention_period" {
  description = "Number of days to retain automated MySQL backups."
  type        = number
  default     = 1

  validation {
    condition     = var.mysql_backup_retention_period >= 0 && var.mysql_backup_retention_period <= 35
    error_message = "MySQL backup retention must be between 0 and 35 days."
  }
}

variable "postgres_backup_retention_period" {
  description = "Number of days to retain automated PostgreSQL backups."
  type        = number
  default     = 1

  validation {
    condition     = var.postgres_backup_retention_period >= 0 && var.postgres_backup_retention_period <= 35
    error_message = "PostgreSQL backup retention must be between 0 and 35 days."
  }
}

variable "skip_final_snapshot" {
  description = "Whether RDS should skip the final snapshot when the instance is destroyed."
  type        = bool
  default     = true
}


variable "cloudwatch_log_retention_days" {
  description = "CloudWatch log retention period in days."
  type        = number
  default     = 30

  validation {
    condition = contains(
      [1, 3, 5, 7, 14, 30, 60, 90, 120, 150, 180, 365, 400, 545, 731, 1827, 3653],
      var.cloudwatch_log_retention_days
    )
    error_message = "cloudwatch_log_retention_days must be a valid CloudWatch retention period."
  }
}

variable "backend_cpu_alarm_threshold" {
  description = "Backend ECS CPU utilization alarm threshold."
  type        = number
  default     = 80

  validation {
    condition     = var.backend_cpu_alarm_threshold > 0 && var.backend_cpu_alarm_threshold <= 100
    error_message = "backend_cpu_alarm_threshold must be between 1 and 100."
  }
}

variable "backend_memory_alarm_threshold" {
  description = "Backend ECS memory utilization alarm threshold."
  type        = number
  default     = 85

  validation {
    condition     = var.backend_memory_alarm_threshold > 0 && var.backend_memory_alarm_threshold <= 100
    error_message = "backend_memory_alarm_threshold must be between 1 and 100."
  }
}

variable "alb_unhealthy_host_threshold" {
  description = "Number of unhealthy ALB targets that triggers an alarm."
  type        = number
  default     = 1

  validation {
    condition     = var.alb_unhealthy_host_threshold >= 1
    error_message = "alb_unhealthy_host_threshold must be at least 1."
  }
}
