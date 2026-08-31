# ECS IAM Role Design

## ECS Task Execution Role

The ECS task execution role is used by Amazon ECS infrastructure.

Current responsibility:

- Pull container images from Amazon ECR.
- Send container logs to Amazon CloudWatch Logs.

The project uses the AWS-managed:

`AmazonECSTaskExecutionRolePolicy`

## ECS Task Role

The ECS task role represents the permissions available to the
Laravel application running inside the ECS task.

The role intentionally has no additional AWS service permissions
until the application requires them.

This follows least-privilege principles.

## Future permissions

Permissions will be added only when required by application features.

Examples:

- AWS Secrets Manager:
  read specific application secrets.
- Amazon S3:
  access specific buckets and prefixes.
- Amazon SQS:
  send or receive from specific queues.

Wildcard permissions and AdministratorAccess must not be granted
to the application task role.

## Separation

Execution role:

ECS infrastructure -> AWS services

Task role:

Laravel application -> AWS services

These roles must remain separate.
