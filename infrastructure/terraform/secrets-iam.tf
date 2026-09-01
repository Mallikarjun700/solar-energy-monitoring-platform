data "aws_iam_policy_document" "ecs_secrets" {
  count = (
    var.database_secret_arn != "" ||
    var.telemetry_database_secret_arn != ""
  ) ? 1 : 0

  statement {
    sid    = "ReadApplicationSecrets"
    effect = "Allow"

    actions = [
      "secretsmanager:GetSecretValue"
    ]

    resources = compact([
      var.database_secret_arn,
      var.telemetry_database_secret_arn
    ])
  }
}

resource "aws_iam_role_policy" "ecs_secrets" {
  count = (
    var.database_secret_arn != "" ||
    var.telemetry_database_secret_arn != ""
  ) ? 1 : 0

  name   = "${local.name_prefix}-ecs-secrets"
  role   = aws_iam_role.ecs_task_execution.id
  policy = data.aws_iam_policy_document.ecs_secrets[0].json
}
