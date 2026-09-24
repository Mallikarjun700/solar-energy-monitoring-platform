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
    sid    = "ECSDescribeServices"
    effect = "Allow"

    actions = [
      "ecs:DescribeServices"
    ]

    resources = [
      aws_ecs_service.backend.id,
      aws_ecs_service.queue_worker.id,
      aws_ecs_service.scheduler.id
    ]
  }

  statement {
    sid    = "ECSDescribeTaskDefinitions"
    effect = "Allow"

    actions = [
      "ecs:DescribeTaskDefinition"
    ]

    resources = [
      aws_ecs_task_definition.backend.arn,
      aws_ecs_task_definition.queue_worker.arn,
      aws_ecs_task_definition.scheduler.arn
    ]
  }

  statement {
    sid    = "ECSDescribeCluster"
    effect = "Allow"

    actions = [
      "ecs:DescribeClusters"
    ]

    resources = [
      aws_ecs_cluster.main.arn
    ]
  }

  statement {
    sid    = "ECSRegisterTaskDefinitions"
    effect = "Allow"

    actions = [
      "ecs:RegisterTaskDefinition"
    ]

    resources = ["*"]
  }

  statement {
    sid    = "ECSUpdateServices"
    effect = "Allow"

    actions = [
      "ecs:UpdateService"
    ]

    resources = [
      aws_ecs_service.backend.id,
      aws_ecs_service.queue_worker.id,
      aws_ecs_service.scheduler.id
    ]
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

      values = [
        "ecs-tasks.amazonaws.com"
      ]
    }
  }
}

resource "aws_iam_role_policy" "github_actions_deployment" {
  name = "${local.name_prefix}-github-actions-deployment"
  role = aws_iam_role.github_actions_deployment.id

  policy = data.aws_iam_policy_document.github_actions_deployment.json
}
