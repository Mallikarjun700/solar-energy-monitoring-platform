data "aws_iam_policy_document" "github_actions_deployment" {
  statement {
    sid    = "ECRAuthentication"
    effect = "Allow"

    actions = [
      "ecr:GetAuthorizationToken"
    ]

    resources = ["*"]
  }

  statement {
      sid    = "PassECSTaskRoles"
      effect = "Allow"

      actions = [
        "iam:PassRole"
      ]

      resources = [
        aws_iam_role.ecs_task_execution.arn,
        aws_iam_role.ecs_task.arn
      ]

      condition {
        test     = "StringEquals"
        variable = "iam:PassedToService"
        values   = [
          "ecs-tasks.amazonaws.com"
        ]
      }
    }

  statement {
    sid    = "ECRPushImages"
    effect = "Allow"

    actions = [
      "ecr:BatchCheckLayerAvailability",
      "ecr:CompleteLayerUpload",
      "ecr:InitiateLayerUpload",
      "ecr:PutImage",
      "ecr:UploadLayerPart"
    ]

    resources = [
      aws_ecr_repository.backend.arn,
      aws_ecr_repository.nginx.arn
    ]
  }

  statement {
    sid    = "ECRReadImages"
    effect = "Allow"

    actions = [
      "ecr:BatchGetImage",
      "ecr:DescribeImages"
    ]

    resources = [
      aws_ecr_repository.backend.arn,
      aws_ecr_repository.nginx.arn
    ]
  }

  statement {
    sid    = "ECSDeployment"
    effect = "Allow"

    actions = [
      "ecs:DescribeServices",
      "ecs:DescribeTaskDefinition",
      "ecs:RegisterTaskDefinition",
      "ecs:UpdateService"
    ]

    resources = ["*"]
  }
}

resource "aws_iam_role_policy" "github_actions_deployment" {
  name = "${local.name_prefix}-github-actions-deployment"
  role = aws_iam_role.github_actions_deployment.id

  policy = data.aws_iam_policy_document.github_actions_deployment.json
}
