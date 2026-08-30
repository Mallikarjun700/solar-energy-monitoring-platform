                         Internet
                            │
                            ▼
                    ┌───────────────┐
                    │ Route 53 / DNS│
                    └───────┬───────┘
                            │
                            ▼
                    ┌───────────────┐
                    │ AWS ALB       │
                    │ HTTPS / TLS   │
                    └───────┬───────┘
                            │
                 ┌──────────┴──────────┐
                 │                     │
                 ▼                     ▼
        ┌─────────────────┐   ┌─────────────────┐
        │ Laravel App     │   │ Laravel App     │
        │ ECS/Fargate     │   │ ECS/Fargate     │
        └────────┬────────┘   └────────┬────────┘
                 │                     │
                 └──────────┬──────────┘
                            │
          ┌─────────────────┼──────────────────┐
          │                 │                  │
          ▼                 ▼                  ▼
    ┌───────────┐     ┌────────────┐     ┌────────────┐
    │ RDS       │     │ RDS        │     │ ElastiCache│
    │ MySQL     │     │ PostgreSQL │     │ Redis      │
    │ Core DB   │     │ Telemetry  │     │ Cache      │
    └───────────┘     └────────────┘     └────────────┘
                            │
                            ▼
                     ┌─────────────┐
                     │ S3 Archive  │
                     │ Telemetry   │
                     └─────────────┘


              Async Processing
                     │
                     ▼
              ┌─────────────┐
              │ Queue       │
              │ Broker      │
              └──────┬──────┘
                     │
             ┌───────┴────────┐
             ▼                ▼
      ┌─────────────┐  ┌─────────────┐
      │ Queue       │  │ Queue       │
      │ Worker      │  │ Worker      │
      │ ECS/Fargate │  │ ECS/Fargate │
      └─────────────┘  └─────────────┘




AWS components:
--------------------------------------------------
| Layer              | AWS target                |
| ------------------ | ------------------------- |
| DNS                | Route 53                  |
| TLS                | ACM                       |
| Load balancing     | Application Load Balancer |
| Application        | ECS Fargate               |
| Queue workers      | ECS Fargate               |
| Scheduler          | ECS / scheduled task      |
| Core relational DB | RDS MySQL                 |
| Telemetry DB       | RDS PostgreSQL            |
| Cache              | ElastiCache Redis         |
| Archive            | S3                        |
| Container registry | ECR                       |
| Secrets            | Secrets Manager           |
| Logs               | CloudWatch                |
| Metrics            | CloudWatch                |
| Networking         | VPC                       |
| IAM                | IAM roles                 |
| CI/CD              | GitHub Actions            |
--------------------------------------------------