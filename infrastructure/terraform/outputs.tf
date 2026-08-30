output "vpc_id" {
  description = "ID of the production VPC."
  value       = aws_vpc.main.id
}

output "public_subnet_ids" {
  description = "IDs of public subnets."
  value       = aws_subnet.public[*].id
}

output "private_app_subnet_ids" {
  description = "IDs of private application subnets."
  value       = aws_subnet.private_app[*].id
}

output "private_data_subnet_ids" {
  description = "IDs of private data subnets."
  value       = aws_subnet.private_data[*].id
}

output "availability_zones" {
  description = "Availability Zones used by the environment."
  value       = var.availability_zones
}

output "ecs_security_group_id" {
  description = "Security group ID for ECS workloads."
  value       = aws_security_group.ecs.id
}

output "alb_security_group_id" {
  description = "Security group ID for the ALB."
  value       = aws_security_group.alb.id
}

output "mysql_security_group_id" {
  description = "Security group ID for MySQL."
  value       = aws_security_group.mysql.id
}

output "postgres_security_group_id" {
  description = "Security group ID for PostgreSQL."
  value       = aws_security_group.postgres.id
}

output "redis_security_group_id" {
  description = "Security group ID for Redis."
  value       = aws_security_group.redis.id
}
output "ecr_repository_url" {
  description = "ECR repository URL for the backend image."
  value       = aws_ecr_repository.backend.repository_url
}

output "ecs_cluster_name" {
  description = "ECS cluster name."
  value       = aws_ecs_cluster.main.name
}

output "ecs_service_name" {
  description = "ECS backend service name."
  value       = aws_ecs_service.backend.name
}

output "cloudwatch_log_group" {
  description = "CloudWatch log group for backend containers."
  value       = aws_cloudwatch_log_group.backend.name
}
