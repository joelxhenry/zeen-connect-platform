#!/bin/bash
set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}=== Zeen Connect VPS Development Setup ===${NC}"

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}Please run as root (sudo bash setup-vps.sh)${NC}"
    exit 1
fi

# Get the actual user (not root)
ACTUAL_USER="${SUDO_USER:-$USER}"

# Update system
echo -e "${YELLOW}Updating system packages...${NC}"
apt-get update && apt-get upgrade -y

# Install Docker if not present
if ! command -v docker &> /dev/null; then
    echo -e "${YELLOW}Installing Docker...${NC}"
    curl -fsSL https://get.docker.com | sh
    systemctl enable docker
    systemctl start docker
else
    echo -e "${GREEN}Docker already installed${NC}"
fi

# Install Docker Compose plugin if not present
if ! docker compose version &> /dev/null; then
    echo -e "${YELLOW}Installing Docker Compose plugin...${NC}"
    apt-get install -y docker-compose-plugin
else
    echo -e "${GREEN}Docker Compose already installed${NC}"
fi

# Add user to docker group
if [ "$ACTUAL_USER" != "root" ]; then
    usermod -aG docker "$ACTUAL_USER"
    echo -e "${GREEN}Added $ACTUAL_USER to docker group${NC}"
fi

# Install Caddy for reverse proxy with automatic SSL
echo -e "${YELLOW}Installing Caddy...${NC}"
apt-get install -y debian-keyring debian-archive-keyring apt-transport-https curl
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg 2>/dev/null || true
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' | tee /etc/apt/sources.list.d/caddy-stable.list > /dev/null
apt-get update
apt-get install -y caddy

# Install useful tools
echo -e "${YELLOW}Installing additional tools...${NC}"
apt-get install -y git htop vim nano

# Create project directory
PROJECT_DIR="/var/www/zeen"
echo -e "${YELLOW}Creating project directory: $PROJECT_DIR${NC}"
mkdir -p "$PROJECT_DIR"
chown -R "$ACTUAL_USER:$ACTUAL_USER" "$PROJECT_DIR"

# Increase inotify watchers for file watching
echo -e "${YELLOW}Configuring inotify watchers...${NC}"
if ! grep -q "fs.inotify.max_user_watches" /etc/sysctl.conf; then
    echo "fs.inotify.max_user_watches=524288" >> /etc/sysctl.conf
    sysctl -p
fi

# Configure firewall (if ufw is installed)
if command -v ufw &> /dev/null; then
    echo -e "${YELLOW}Configuring firewall...${NC}"
    ufw allow 22/tcp   # SSH
    ufw allow 80/tcp   # HTTP
    ufw allow 443/tcp  # HTTPS
    echo -e "${GREEN}Firewall configured (SSH, HTTP, HTTPS allowed)${NC}"
fi

echo ""
echo -e "${GREEN}=== Setup Complete ===${NC}"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo ""
echo "1. Clone your repository:"
echo "   git clone <your-repo-url> $PROJECT_DIR"
echo "   cd $PROJECT_DIR"
echo ""
echo "2. Configure environment:"
echo "   cp .env.dev.example .env"
echo "   nano .env  # Update with your domain and passwords"
echo ""
echo "3. Copy Caddyfile.dev to Caddy config:"
echo "   sudo cp Caddyfile.dev /etc/caddy/Caddyfile"
echo "   sudo nano /etc/caddy/Caddyfile  # Update domain and email"
echo "   sudo systemctl restart caddy"
echo ""
echo "4. Start the containers:"
echo "   docker compose -f docker-compose.dev.yml up -d --build"
echo ""
echo "5. Run initial setup:"
echo "   docker compose -f docker-compose.dev.yml exec app php artisan key:generate"
echo "   docker compose -f docker-compose.dev.yml exec app php artisan migrate"
echo ""
echo -e "${GREEN}VS Code Remote SSH:${NC}"
echo "1. Install 'Remote - SSH' extension in VS Code"
echo "2. Connect to: ssh $ACTUAL_USER@<your-vps-ip>"
echo "3. Open folder: $PROJECT_DIR"
echo ""
echo -e "${YELLOW}Note: Log out and back in for docker group to take effect${NC}"
