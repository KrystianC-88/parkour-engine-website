<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Engine</title>
</head>
<body>
    
<div class="game-container">
    <canvas id="game" width="600" height="400"></canvas>
</div>

<script>
class Tile {
    constructor(x, y, variant, type) {
        this.x = x;
        this.y = y;
        this.variant = variant;
        this.type = type;
    }
}

class Map {
    constructor() {
        this.tiles = [];
    }

    addTile(x, y, variant, type) {
        this.tiles.push(new Tile(x, y, variant, type));
    }
}

class Player {
    constructor(x, y, width, height) {
        this.x = x;
        this.y = y;
        this.width = width;
        this.height = height;
        this.velocityX = 0;
        this.velocityY = 0;
        this.jumping = false;
    }
}

class Game {
    constructor(canvasId, tileSize) {
        this.canvas = document.getElementById(canvasId);
        this.ctx = this.canvas.getContext("2d");
        this.tileSize = tileSize;
        this.map = new Map();
        this.player = new Player(0, 0, tileSize, tileSize * 2);
        this.gravity = 0.5;
        this.camera = { x: 0, y: 0 };

        // tileVariants:
        this.tileVariants = {
            "grass": "#00FF00",
            "platform": "#808080"
        }

        // Generate map
        this.generateMap();

        // Bind controls
        document.addEventListener("keydown", this.handleKeyDown.bind(this));
        document.addEventListener("keyup", this.handleKeyUp.bind(this));
    }

    generateMap() {
        // Add tiles to the map
        this.map.addTile(10, 15, "grass", "collision");
        this.map.addTile(5, 10, "platform", "collision");
        for (let i = 0; i < 50; i++) {
            this.map.addTile(i, 15, "grass", "collision");
        }
        // this.map.addTile(10, 14, "platform", "collision")
        // this.map.addTile(10, 13, "platform", "collision")
        for (let i = 12; i > 0; i--) {
            this.map.addTile(10, i, "platform", "collision");
        }

        for (let i = 12; i > 0; i--) {
            this.map.addTile(15, i, "platform", "collision");
        }
    }

    handleKeyDown(event) {
        if (event.key === "ArrowLeft") {
            this.player.velocityX = -3;
        } else if (event.key === "ArrowRight") {
            this.player.velocityX = 3;
        } else if ( (event.key === "ArrowUp" || event.key === " " ) && !this.player.jumping) {
            this.player.velocityY = -10;
            this.player.jumping = true;
        }
    }

    handleKeyUp(event) {
        if (event.key === "ArrowLeft" || event.key === "ArrowRight") {
            this.player.velocityX = 0;
        }
    }

    checkCollision(tile) {
    if (tile.type !== "collision") return;

    // Player bounding box
    const playerLeft = this.player.x;
    const playerRight = this.player.x + this.player.width;
    const playerTop = this.player.y;
    const playerBottom = this.player.y + this.player.height;

    // Tile bounding box
    const tileLeft = tile.x * this.tileSize;
    const tileRight = (tile.x + 1) * this.tileSize;
    const tileTop = tile.y * this.tileSize;
    const tileBottom = (tile.y + 1) * this.tileSize;

    // Check for overlap
    if (
        playerRight > tileLeft &&
        playerLeft < tileRight &&
        playerBottom > tileTop &&
        playerTop < tileBottom
    ) {
        // Collision detected
        const playerCenterX = this.player.x + this.player.width / 2;
        const playerCenterY = this.player.y + this.player.height / 2;
        const tileCenterX = tileLeft + this.tileSize / 2;
        const tileCenterY = tileTop + this.tileSize / 2;
        
        const dx = playerCenterX - tileCenterX;
        const dy = playerCenterY - tileCenterY;
        const width = (this.player.width + this.tileSize) / 2;
        const height = (this.player.height + this.tileSize) / 2;
        const crossWidth = width * dy;
        const crossHeight = height * dx;

        // Check collision direction
        if (Math.abs(dx) <= width && Math.abs(dy) <= height) {
            if (crossWidth > crossHeight) {
                if (crossWidth > -crossHeight) {
                    // Bottom collision
                    this.player.y = tileBottom;
                    this.player.velocityY = 0;
                    this.player.jumping = false; // Reset jumping state only when falling
                } else {
                    // Left collision
                    this.player.x = tileLeft - this.player.width;
                    this.player.velocityX = 0;
                }
            } else {
                if (crossWidth > -crossHeight) {
                    // Right collision
                    this.player.x = tileRight;
                    this.player.velocityX = 0;
                } else {
                    // Top collision
                    this.player.y = tileTop - this.player.height;
                    this.player.velocityY = 0;
                }
            }
        }
    }

    // Check if the player is on the ground
    if (this.player.velocityY >= 0 && playerBottom === tileTop) {
        this.player.jumping = false;
    }
}







    update() {

        // Update camera position to follow the player
        this.camera.x = Math.max(0, this.player.x - this.canvas.width / 2 + this.player.width / 2);
        this.camera.y = Math.max(0, this.player.y - this.canvas.height / 2 + this.player.height / 2);

        // Apply gravity
        this.player.velocityY += this.gravity;
        this.player.x += this.player.velocityX;
        this.player.y += this.player.velocityY;

        // Clear canvas
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

        // Draw tiles within camera view and check collision
        for (let tile of this.map.tiles) {
            const tileX = tile.x * this.tileSize - this.camera.x;
            const tileY = tile.y * this.tileSize - this.camera.y;

            // Draw tile
            this.ctx.fillStyle = this.tileVariants[tile.variant];
            this.ctx.fillRect(tileX, tileY, this.tileSize, this.tileSize);

            // Check collision
            this.checkCollision(tile);
        }

        // Draw player
        this.ctx.fillStyle = "#0000FF"; // Player color
        this.ctx.fillRect(this.player.x - this.camera.x, this.player.y - this.camera.y, this.player.width, this.player.height);

        // Repeat
        requestAnimationFrame(this.update.bind(this));
    }

    start() {
        this.update();
    }
}

const game = new Game("game", 20);
game.start();


</script>
</body>
</html>
