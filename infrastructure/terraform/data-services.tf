resource "aws_db_subnet_group" "mysql" {
  name       = "${local.name_prefix}-mysql"
  subnet_ids = aws_subnet.private_data[*].id

  tags = {
    Name = "${local.name_prefix}-mysql-subnet-group"
  }
}

resource "aws_db_subnet_group" "postgres" {
  name       = "${local.name_prefix}-postgres"
  subnet_ids = aws_subnet.private_data[*].id

  tags = {
    Name = "${local.name_prefix}-postgres-subnet-group"
  }
}

resource "aws_db_instance" "mysql" {
  identifier = "${local.name_prefix}-mysql"

  engine         = "mysql"
  engine_version = "8.0"

  instance_class        = var.mysql_instance_class
  allocated_storage     = var.mysql_allocated_storage
  max_allocated_storage = var.mysql_max_allocated_storage
  storage_type          = "gp3"

  db_name  = var.database_name
  username = var.database_username

  manage_master_user_password = true

  port = 3306

  db_subnet_group_name   = aws_db_subnet_group.mysql.name
  vpc_security_group_ids = [aws_security_group.mysql.id]

  publicly_accessible = false

  multi_az            = false
  skip_final_snapshot = true
  deletion_protection = var.enable_deletion_protection

  backup_retention_period = 1

  auto_minor_version_upgrade = true

  tags = {
    Name = "${local.name_prefix}-mysql"
  }
}

resource "aws_db_instance" "postgres" {
  identifier = "${local.name_prefix}-postgres"

  engine         = "postgres"
  engine_version = "16"

  instance_class        = var.postgres_instance_class
  allocated_storage     = var.postgres_allocated_storage
  max_allocated_storage = var.postgres_max_allocated_storage
  storage_type          = "gp3"

  db_name  = var.telemetry_database_name
  username = var.telemetry_database_username

  manage_master_user_password = true

  port = 5432

  db_subnet_group_name   = aws_db_subnet_group.postgres.name
  vpc_security_group_ids = [aws_security_group.postgres.id]

  publicly_accessible = false

  multi_az            = false
  skip_final_snapshot = true
  deletion_protection = var.enable_deletion_protection

  backup_retention_period = 1

  auto_minor_version_upgrade = true

  tags = {
    Name = "${local.name_prefix}-postgres"
  }
}

resource "aws_elasticache_subnet_group" "redis" {
  name       = "${local.name_prefix}-redis"
  subnet_ids = aws_subnet.private_data[*].id

  tags = {
    Name = "${local.name_prefix}-redis-subnet-group"
  }
}

resource "aws_elasticache_replication_group" "redis" {
  replication_group_id = "${local.name_prefix}-redis"

  description = "Redis cache and queue backend for the solar platform."

  engine             = "redis"
  node_type          = var.redis_node_type
  num_cache_clusters = 1

  port = 6379

  subnet_group_name  = aws_elasticache_subnet_group.redis.name
  security_group_ids = [aws_security_group.redis.id]

  automatic_failover_enabled = false
  multi_az_enabled           = false

  at_rest_encryption_enabled = true
  transit_encryption_enabled = true

  tags = {
    Name = "${local.name_prefix}-redis"
  }
}
