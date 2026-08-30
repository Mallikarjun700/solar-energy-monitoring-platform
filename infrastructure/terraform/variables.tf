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
  default     = "latest"
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
  default     = 2
}
