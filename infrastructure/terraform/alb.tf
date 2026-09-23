resource "aws_lb" "main" {
  name               = "${local.short_name_prefix}-alb"
  internal           = false
  load_balancer_type = "application"

  security_groups = [
    aws_security_group.alb.id
  ]

  subnets = aws_subnet.public[*].id

  enable_deletion_protection = var.enable_deletion_protection

  tags = {
    Name = "${local.name_prefix}-alb"
  }
}

resource "aws_lb_target_group" "backend" {
  name        = "${local.short_name_prefix}-backend"
  port        = 80
  protocol    = "HTTP"
  target_type = "ip"

  deregistration_delay = 30

  vpc_id = aws_vpc.main.id

  health_check {
    enabled             = true
    path                = "/up"
    protocol            = "HTTP"
    port                = "traffic-port"
    healthy_threshold   = 2
    unhealthy_threshold = 3
    timeout             = 5
    interval            = 30
    matcher             = "200"
  }

  tags = {
    Name = "${local.name_prefix}-backend-tg"
  }
}

resource "aws_lb_listener" "http" {
  load_balancer_arn = aws_lb.main.arn
  port              = 80
  protocol          = "HTTP"

  default_action {
    type = var.enable_https && var.domain_name != "" ? "redirect" : "forward"

    target_group_arn = var.enable_https && var.domain_name != "" ? null : aws_lb_target_group.backend.arn

    dynamic "redirect" {
      for_each = var.enable_https && var.domain_name != "" ? [1] : []

      content {
        port        = "443"
        protocol    = "HTTPS"
        status_code = "HTTP_301"
      }
    }
  }
}

resource "aws_lb_listener" "https" {
  count = var.enable_https && var.domain_name != "" ? 1 : 0

  load_balancer_arn = aws_lb.main.arn
  port              = 443
  protocol          = "HTTPS"
  ssl_policy        = "ELBSecurityPolicy-TLS13-1-2-2021-06"

  certificate_arn = aws_acm_certificate.main[0].arn

  default_action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.backend.arn
  }
}
