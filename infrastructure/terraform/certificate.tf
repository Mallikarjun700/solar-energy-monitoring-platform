resource "aws_acm_certificate" "main" {
  count = var.enable_https && var.domain_name != "" ? 1 : 0

  domain_name       = var.domain_name
  validation_method = "DNS"

  lifecycle {
    create_before_destroy = true
  }

  tags = {
    Name = "${local.name_prefix}-certificate"
  }
}
