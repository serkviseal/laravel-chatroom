# VPC for private networking between resources
resource "digitalocean_vpc" "chatroom" {
  name   = "chatroom-${var.environment}"
  region = var.do_region
}

module "droplet" {
  source          = "./modules/droplet"
  environment     = var.environment
  region          = var.do_region
  vpc_id          = digitalocean_vpc.chatroom.id
  ssh_fingerprint = var.ssh_fingerprint
  image_name      = "ghcr.io/${var.github_owner}/${var.github_repo}:${var.environment == "production" ? "latest" : "staging"}"
}

module "database" {
  source      = "./modules/database"
  environment = var.environment
  region      = var.do_region
  vpc_id      = digitalocean_vpc.chatroom.id
  db_password = var.db_password
}

module "redis" {
  source      = "./modules/redis"
  environment = var.environment
  region      = var.do_region
  vpc_id      = digitalocean_vpc.chatroom.id
}

module "spaces" {
  source      = "./modules/spaces"
  environment = var.environment
  region      = var.do_region
}

module "github" {
  source       = "./modules/github"
  repo         = var.github_repo
  owner        = var.github_owner
  staging_host = module.droplet.staging_ip
  prod_host    = module.droplet.production_ip
}

# DNS records
resource "digitalocean_domain" "chatroom" {
  name = var.app_domain
}

resource "digitalocean_record" "app" {
  domain = digitalocean_domain.chatroom.name
  type   = "A"
  name   = "@"
  value  = module.droplet.ip_address
  ttl    = 300
}
