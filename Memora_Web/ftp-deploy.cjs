const FtpDeploy = require("ftp-deploy");
require("dotenv").config();

const MAX_RETRIES = 3;
const RETRY_DELAY = 5000; // 5 seconds

async function deployWithRetry(retryCount = 0) {
    const ftpDeploy = new FtpDeploy();

    const config = {
        user: process.env.FTP_USER || process.env.FTP_USERNAME,
        password: process.env.FTP_PASSWORD,
        host: process.env.FTP_HOST,
        port: parseInt(process.env.FTP_PORT) || 21,
        localRoot: __dirname,
        remoteRoot: process.env.FTP_ROOT || "/public_html",
        include: [
            // Root files
            "artisan",
            "composer.json",
            "composer.lock",
            "package.json",
            "vite.config.js",
            "postcss.config.js",
            "tailwind.config.js",
            ".htaccess",
            ".gitignore",
            ".gitattributes",
            // Application directories
            "app/**",
            "bootstrap/**",
            "config/**",
            "database/factories/**",
            "database/migrations/**",
            "database/seeders/**",
            // Public (excluding storage symlink)
            "public/index.php",
            "public/robots.txt",
            "public/favicon.ico",
            "public/favicon.jpg",
            "public/build/**",
            "public/downloads/**",
            // Resources & routes
            "resources/**",
            "routes/**",
            // Storage (skeleton only, not logs)
            "storage/app/**",
            "storage/framework/**",
            // GitHub workflows
            ".github/**",
        ],
        exclude: [
            "storage/logs/**",
            "storage/framework/cache/**",
            "storage/framework/views/**",
            "storage/framework/sessions/**",
            "bootstrap/cache/**",
            "database/database.sqlite",
            "node_modules/**",
            ".git/**",
        ],
        deleteRemote: false,
        forcePasv: true,
        sftp: false,
        // Enhanced connection settings
        connTimeout: 3000000, // 30 seconds
        pasvTimeout: 3000000, // 30 seconds
        keepalive: 3000000, // Send keepalive every 30 seconds
    };

    const vendorUpload = process.env.FTP_UPLOAD_VENDOR === "true";
    if (vendorUpload) {
        config.include.push("vendor/**");
        console.log("⚠️  Including vendor/ folder (this may take a while)...");
    } else {
        console.log("ℹ️  Skipping vendor/ — run 'composer install' on the server after deploy.");
    }

    console.log(`\n🚀 Starting FTP Deployment to ${config.host}`);
    console.log(`📂 Remote Root: ${config.remoteRoot}`);
    if (retryCount > 0) {
        console.log(`🔄 Retry attempt ${retryCount}/${MAX_RETRIES}\n`);
    } else {
        console.log("");
    }

    return new Promise((resolve, reject) => {
        ftpDeploy
            .deploy(config)
            .then((res) => {
                console.log(`\n\n✅ Deployment complete! Uploaded ${res.length} files.`);
                resolve(res);
            })
            .catch((err) => {
                reject(err);
            });

        ftpDeploy.on("uploading", function (data) {
            process.stdout.write(
                `\r📤 [${data.transferredFileCount}/${data.totalFilesCount}] ${String(data.filename)
                    .substring(0, 65)
                    .padEnd(65)}`
            );
        });

        ftpDeploy.on("upload-error", function (data) {
            console.error(`\n⚠️  Error: ${data.filename} — ${data.err}`);
        });
    });
}

// Main execution with retry logic
deployWithRetry()
    .catch(async (err) => {
        const retryCount = parseInt(process.env.RETRY_COUNT || "0");

        if (retryCount < MAX_RETRIES && (err.code === "ECONNRESET" || err.code === "ETIMEDOUT" || err.code === "ENOTFOUND")) {
            console.error(`\n⚠️  Connection error: ${err.code}`);
            console.log(`⏳ Waiting ${RETRY_DELAY / 1000} seconds before retry...\n`);

            process.env.RETRY_COUNT = retryCount + 1;
            await new Promise((resolve) => setTimeout(resolve, RETRY_DELAY));

            return deployWithRetry(retryCount + 1);
        } else {
            console.error("\n❌ Deployment error:", err.message || err);
            if (retryCount >= MAX_RETRIES) {
                console.error(`\n❌ Max retries (${MAX_RETRIES}) reached. Deployment failed.`);
            }
            process.exit(1);
        }
    });