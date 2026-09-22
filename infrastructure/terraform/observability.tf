resource "aws_cloudwatch_log_group" "api" {
  name              = "/ecs/${local.name_prefix}/api"
  retention_in_days = var.cloudwatch_log_retention_days

  tags = {
    Name = "${local.name_prefix}-api-logs"
  }
}

resource "aws_cloudwatch_log_group" "nginx" {
  name              = "/ecs/${local.name_prefix}/nginx"
  retention_in_days = var.cloudwatch_log_retention_days

  tags = {
    Name = "${local.name_prefix}-nginx-logs"
  }
}

resource "aws_cloudwatch_log_group" "queue" {
  name              = "/ecs/${local.name_prefix}/queue"
  retention_in_days = var.cloudwatch_log_retention_days

  tags = {
    Name = "${local.name_prefix}-queue-logs"
  }
}

resource "aws_cloudwatch_log_group" "scheduler" {
  name              = "/ecs/${local.name_prefix}/scheduler"
  retention_in_days = var.cloudwatch_log_retention_days

  tags = {
    Name = "${local.name_prefix}-scheduler-logs"
  }
}


resource "aws_sns_topic" "alerts" {
  name = "${local.name_prefix}-alerts"

  tags = {
    Name = "${local.name_prefix}-alerts"
  }
}

resource "aws_cloudwatch_metric_alarm" "backend_cpu_high" {
  alarm_name        = "${local.name_prefix}-backend-cpu-high"
  alarm_description = "Backend ECS service CPU utilization is high."

  namespace   = "AWS/ECS"
  metric_name = "CPUUtilization"

  dimensions = {
    ClusterName = aws_ecs_cluster.main.name
    ServiceName = aws_ecs_service.backend.name
  }

  statistic           = "Average"
  period              = 300
  evaluation_periods  = 2
  datapoints_to_alarm = 2

  threshold           = var.backend_cpu_alarm_threshold
  comparison_operator = "GreaterThanThreshold"

  treat_missing_data = "notBreaching"
  alarm_actions      = [aws_sns_topic.alerts.arn]
}

resource "aws_cloudwatch_metric_alarm" "backend_memory_high" {
  alarm_name        = "${local.name_prefix}-backend-memory-high"
  alarm_description = "Backend ECS service memory utilization is high."

  namespace   = "AWS/ECS"
  metric_name = "MemoryUtilization"

  dimensions = {
    ClusterName = aws_ecs_cluster.main.name
    ServiceName = aws_ecs_service.backend.name
  }

  statistic           = "Average"
  period              = 300
  evaluation_periods  = 2
  datapoints_to_alarm = 2

  threshold           = var.backend_memory_alarm_threshold
  comparison_operator = "GreaterThanThreshold"

  treat_missing_data = "notBreaching"
  alarm_actions      = [aws_sns_topic.alerts.arn]
}

resource "aws_cloudwatch_metric_alarm" "backend_running_tasks_low" {
  alarm_name        = "${local.name_prefix}-backend-running-tasks-low"
  alarm_description = "Backend ECS service has no running tasks."

  namespace   = "ECS/ContainerInsights"
  metric_name = "RunningTaskCount"

  dimensions = {
    ClusterName = aws_ecs_cluster.main.name
    ServiceName = aws_ecs_service.backend.name
  }

  statistic           = "Minimum"
  period              = 300
  evaluation_periods  = 2
  datapoints_to_alarm = 2

  threshold           = 1
  comparison_operator = "LessThanThreshold"

  treat_missing_data = "breaching"
  alarm_actions      = [aws_sns_topic.alerts.arn]
}

resource "aws_cloudwatch_metric_alarm" "alb_unhealthy_targets" {
  alarm_name        = "${local.name_prefix}-alb-unhealthy-targets"
  alarm_description = "ALB has unhealthy backend targets."

  namespace   = "AWS/ApplicationELB"
  metric_name = "UnHealthyHostCount"

  dimensions = {
    LoadBalancer = aws_lb.main.arn_suffix
    TargetGroup  = aws_lb_target_group.backend.arn_suffix
  }

  statistic           = "Maximum"
  period              = 300
  evaluation_periods  = 2
  datapoints_to_alarm = 2

  threshold           = var.alb_unhealthy_host_threshold
  comparison_operator = "GreaterThanOrEqualToThreshold"

  treat_missing_data = "notBreaching"
  alarm_actions      = [aws_sns_topic.alerts.arn]
}

resource "aws_cloudwatch_metric_alarm" "alb_5xx" {
  alarm_name        = "${local.name_prefix}-alb-5xx"
  alarm_description = "ALB is returning elevated HTTP 5xx responses."

  namespace   = "AWS/ApplicationELB"
  metric_name = "HTTPCode_ELB_5XX_Count"

  dimensions = {
    LoadBalancer = aws_lb.main.arn_suffix
  }

  statistic           = "Sum"
  period              = 300
  evaluation_periods  = 2
  datapoints_to_alarm = 2

  threshold           = 10
  comparison_operator = "GreaterThanThreshold"

  treat_missing_data = "notBreaching"
  alarm_actions      = [aws_sns_topic.alerts.arn]
}

resource "aws_cloudwatch_metric_alarm" "mysql_cpu_high" {
  alarm_name        = "${local.name_prefix}-mysql-cpu-high"
  alarm_description = "MySQL RDS CPU utilization is high."

  namespace   = "AWS/RDS"
  metric_name = "CPUUtilization"

  dimensions = {
    DBInstanceIdentifier = aws_db_instance.mysql.id
  }

  statistic           = "Average"
  period              = 300
  evaluation_periods  = 2
  datapoints_to_alarm = 2

  threshold           = 80
  comparison_operator = "GreaterThanThreshold"

  treat_missing_data = "notBreaching"
  alarm_actions      = [aws_sns_topic.alerts.arn]
}

resource "aws_cloudwatch_metric_alarm" "mysql_storage_low" {
  alarm_name        = "${local.name_prefix}-mysql-storage-low"
  alarm_description = "MySQL RDS free storage is low."

  namespace   = "AWS/RDS"
  metric_name = "FreeStorageSpace"

  dimensions = {
    DBInstanceIdentifier = aws_db_instance.mysql.id
  }

  statistic           = "Minimum"
  period              = 300
  evaluation_periods  = 2
  datapoints_to_alarm = 2

  threshold           = 5368709120
  comparison_operator = "LessThanThreshold"

  treat_missing_data = "breaching"
  alarm_actions      = [aws_sns_topic.alerts.arn]
}

resource "aws_cloudwatch_metric_alarm" "postgres_cpu_high" {
  alarm_name        = "${local.name_prefix}-postgres-cpu-high"
  alarm_description = "Telemetry PostgreSQL CPU utilization is high."

  namespace   = "AWS/RDS"
  metric_name = "CPUUtilization"

  dimensions = {
    DBInstanceIdentifier = aws_db_instance.postgres.id
  }

  statistic           = "Average"
  period              = 300
  evaluation_periods  = 2
  datapoints_to_alarm = 2

  threshold           = 80
  comparison_operator = "GreaterThanThreshold"

  treat_missing_data = "notBreaching"
  alarm_actions      = [aws_sns_topic.alerts.arn]
}

resource "aws_cloudwatch_metric_alarm" "postgres_storage_low" {
  alarm_name        = "${local.name_prefix}-postgres-storage-low"
  alarm_description = "Telemetry PostgreSQL free storage is low."

  namespace   = "AWS/RDS"
  metric_name = "FreeStorageSpace"

  dimensions = {
    DBInstanceIdentifier = aws_db_instance.postgres.id
  }

  statistic           = "Minimum"
  period              = 300
  evaluation_periods  = 2
  datapoints_to_alarm = 2

  threshold           = 5368709120
  comparison_operator = "LessThanThreshold"

  treat_missing_data = "breaching"
  alarm_actions      = [aws_sns_topic.alerts.arn]
}
