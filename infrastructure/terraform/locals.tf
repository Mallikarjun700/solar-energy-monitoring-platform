locals {
  name_prefix = "${var.project_name}-${var.environment}"

  # Short identifiers for AWS resources with strict name limits.
  short_name_prefix = "sem-${var.environment}"
}
