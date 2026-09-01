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
