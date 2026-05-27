#cloud-config
package_update: true
packages:
  - docker.io
  - docker-compose-plugin

runcmd:
  - systemctl enable --now docker
  - mkdir -p /opt/chatroom
  - docker pull ${image_name}
  - |
    cat > /opt/chatroom/docker-compose.prod.yml << 'EOF'
    services:
      app:
        image: ${image_name}
        env_file: .env
        volumes:
          - storage:/var/www/html/storage
        networks: [chatroom]

      nginx:
        image: nginx:alpine
        ports: ["80:80", "443:443"]
        volumes:
          - ./nginx.conf:/etc/nginx/conf.d/default.conf:ro
        depends_on: [app]
        networks: [chatroom]

      reverb:
        image: ${image_name}
        command: php artisan reverb:start --host=0.0.0.0 --port=8080
        env_file: .env
        networks: [chatroom]

      horizon:
        image: ${image_name}
        command: php artisan horizon
        env_file: .env
        networks: [chatroom]

    networks:
      chatroom:

    volumes:
      storage:
    EOF
  - cd /opt/chatroom && docker compose -f docker-compose.prod.yml up -d
