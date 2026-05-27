variable "environment" {}
variable "region" {}
variable "vpc_id" {}
variable "db_password" { sensitive = true }

resource "digitalocean_database_cluster" "postgres" {
  name       = "chatroom-${var.environment}-db"
  engine     = "pg"
  version    = "15"
  size       = var.environment == "production" ? "db-s-2vcpu-4gb" : "db-s-1vcpu-1gb"
  region     = var.region
  node_count = var.environment == "production" ? 2 : 1
  private_network_uuid = var.vpc_id
}

resource "digitalocean_database_db" "chatroom" {
  cluster_id = digitalocean_database_cluster.postgres.id
  name       = "chatroom"
}

resource "digitalocean_database_user" "app" {
  cluster_id = digitalocean_database_cluster.postgres.id
  name       = "chatroom_app"
}

output "host" {
  value     = digitalocean_database_cluster.postgres.private_host
  sensitive = true
}

output "port" {
  value = digitalocean_database_cluster.postgres.port
}

output "username" {
  value = digitalocean_database_user.app.name
}

output "password" {
  value     = digitalocean_database_user.app.password
  sensitive = true
}
