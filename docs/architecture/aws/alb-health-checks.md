# ALB Health Checks

## Traffic Flow

```text
Internet
   |
   v
Application Load Balancer
   |
   v
Target Group
   |
   v
ECS Nginx container
   |
   v
Laravel application
