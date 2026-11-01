#!/bin/bash

# Production Deployment Verification Script
# Run this after deploying all 5 sites

set -e

echo "========================================="
echo "  Multi-Brand Deployment Verification"
echo "========================================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test static sites
echo -e "${YELLOW}Testing Static Sites...${NC}"
echo ""

for site in redstar blueline greenpromo amberlane; do
    echo -n "  $site.transportmarketing.net... "
    STATUS=$(curl -sI https://$site.transportmarketing.net | grep -E "HTTP|strict-transport" | head -1)
    if [[ $STATUS == *"200"* ]]; then
        echo -e "${GREEN}✓ PASS${NC}"
    else
        echo -e "${RED}✗ FAIL${NC} ($STATUS)"
    fi
done

echo ""

# Test Laravel admin
echo -e "${YELLOW}Testing Laravel Admin Platform...${NC}"
echo ""

echo -n "  manage.redstar.transportmarketing.net/admin... "
STATUS=$(curl -sI https://manage.redstar.transportmarketing.net/admin | grep -E "HTTP" | head -1)
if [[ $STATUS == *"200"* ]]; then
    echo -e "${GREEN}✓ PASS${NC}"
else
    echo -e "${RED}✗ FAIL${NC} ($STATUS)"
fi

echo ""

# Verify Laravel data (requires SSH access)
echo -e "${YELLOW}Verifying Laravel Data (SSH required)...${NC}"
echo ""

ssh forge@64.227.66.233 "cd /home/forge/manage.redstar.transportmarketing.net && php artisan tinker --execute='
  echo \"Entities: \" . \App\Models\Entity::count() . PHP_EOL;
  echo \"Clients: \" . \App\Models\Client::count() . PHP_EOL;
  echo \"Projects: \" . \App\Models\Project::count() . PHP_EOL;
  echo \"Tasks: \" . \App\Models\Task::count() . PHP_EOL;
  echo \"Payments: \" . \App\Models\Payment::count() . PHP_EOL;
  echo \"Contacts: \" . \App\Models\Contact::count() . PHP_EOL;
'" 2>/dev/null

echo ""
echo -e "${GREEN}=========================================${NC}"
echo -e "${GREEN}  Deployment Verification Complete${NC}"
echo -e "${GREEN}=========================================${NC}"
echo ""
echo "Expected Results:"
echo "  - All static sites: HTTP/2 200"
echo "  - Laravel admin: HTTP/2 200"
echo "  - Entities: 4"
echo "  - Clients: 10"
echo "  - Projects: 22"
echo "  - Tasks: 45"
echo "  - Payments: 17"
echo "  - Contacts: 25"
