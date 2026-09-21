data "aws_iam_policy_document" "ecs_execution" {
  statement {
    sid    = "ECRAuthorization"
    effect = "Allow"

    actions = [
      "ecr:GetAuthorizationToken"
    ]

    resources = ["*"]
  }

  statement {
    sid    = "ECRImagePull"
    effect = "Allow"

    actions = [
      "ecr:BatchCheckLayerAvailability",
      "ecr:GetDownloadUrlForLayer",
      "ecr:BatchGetImage"
    ]

    resources = [
      aws_ecr_repository.backend.arn,
      aws_ecr_repository.nginx.arn
    ]
  }

  statement {
    sid    = "CloudWatchLogs"
    effect = "Allow"

    actions = [
      "logs:CreateLogStream",
      "logs:PutLogEvents"
    ]

    resources = [
      "${aws_cloudwatch_log_group.api.arn}:*",
      "${aws_cloudwatch_log_group.nginx.arn}:*",
      "${aws_cloudwatch_log_group.queue.arn}:*",
      "${aws_cloudwatch_log_group.scheduler.arn}:*"
    ]
  }
}

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

resource "aws_iam_role_policy" "ecs_execution" {
  name = "${local.name_prefix}-ecs-execution"
  role = aws_iam_role.ecs_task_execution.id

  policy = data.aws_iam_policy_document.ecs_execution.json
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
