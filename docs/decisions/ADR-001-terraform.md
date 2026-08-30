AWS VPC
│
├── Internet Gateway
│
├── Public AZ-a
│   └── NAT Gateway
│
├── Public AZ-b
│   └── NAT Gateway
│
├── Private App AZ-a
│   └── ECS
│
├── Private App AZ-b
│   └── ECS
│
├── Private Data AZ-a
│   ├── RDS MySQL
│   ├── RDS PostgreSQL
│   └── Redis
│
└── Private Data AZ-b
    ├── RDS MySQL
    ├── RDS PostgreSQL
    └── Redis