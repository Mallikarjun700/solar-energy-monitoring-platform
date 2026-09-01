# GitHub Actions AWS Authentication

## Purpose

The deployment pipeline is designed to authenticate GitHub Actions with AWS
using OpenID Connect (OIDC).

Long-lived AWS access keys should not be stored in GitHub Actions secrets.

## Authentication Flow

```text
GitHub Actions
      |
      | OIDC identity token
      v
GitHub OIDC Provider in AWS
      |
      | AssumeRoleWithWebIdentity
      v
AWS IAM Deployment Role
      |
      +---- ECR
      |
      +---- ECS
      |
      +---- CloudWatch
