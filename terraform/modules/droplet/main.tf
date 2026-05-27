variable "environment" {}
variable "region" {}
variable "vpc_id" {}
variable "ssh_fingerprint" {}
variable "image_name" {}

locals {
  size = var.environment == "production" ? "s-2vcpu-4gb" : "s-1vcpu-2gb"
}

resource "digitalocean_droplet" "app" {
  name     = "chatroom-${var.environment}"
  region   = var.region
  size     = local.size
  image    = "ubuntu-22-04-x64"
  vpc_uuid = var.vpc_id
  ssh_keys = [var.ssh_fingerprint]

  user_data = templatefile("${path.module}/cloud-init.yml.tpl", {
    image_name = var.image_name
  })

  tags = ["chatroom", var.environment]
}

resource "digitalocean_floating_ip" "app" {
  droplet_id = digitalocean_droplet.app.id
  region     = var.region
}

resource "digitalocean_firewall" "app" {
  name        = "chatroom-${var.environment}"
  droplet_ids = [digitalocean_droplet.app.id]

  inbound_rule {
    protocol         = "tcp"
    port_range       = "22"
    source_addresses = ["0.0.0.0/0", "::/0"]
  }

  inbound_rule {
    protocol         = "tcp"
    port_range       = "80"
    source_addresses = ["0.0.0.0/0", "::/0"]
  }

  inbound_rule {
    protocol         = "tcp"
    port_range       = "443"
    source_addresses = ["0.0.0.0/0", "::/0"]
  }

  outbound_rule {
    protocol              = "tcp"
    port_range            = "all"
    destination_addresses = ["0.0.0.0/0", "::/0"]
  }

  outbound_rule {
    protocol              = "udp"
    port_range            = "all"
    destination_addresses = ["0.0.0.0/0", "::/0"]
  }
}

output "ip_address" {
  value = digitalocean_floating_ip.app.ip_address
}

output "staging_ip" {
  value = var.environment == "staging" ? digitalocean_floating_ip.app.ip_address : ""
}

output "production_ip" {
  value = var.environment == "production" ? digitalocean_floating_ip.app.ip_address : ""
}
