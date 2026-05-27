variable "environment" {}
variable "region" {}
variable "vpc_id" {}

resource "digitalocean_database_cluster" "redis" {
  name       = "chatroom-${var.environment}-redis"
  engine     = "redis"
  version    = "7"
  size       = var.environment == "production" ? "db-s-1vcpu-2gb" : "db-s-1vcpu-1gb"
  region     = var.region
  node_count = 1
  private_network_uuid = var.vpc_id
}

output "host" {
  value     = digitalocean_database_cluster.redis.private_host
  sensitive = true
}

output "port" {
  value = digitalocean_database_cluster.redis.port
}

output "password" {
  value     = digitalocean_database_cluster.redis.password
  sensitive = true
}
