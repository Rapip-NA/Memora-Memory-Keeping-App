import os
import sys
import shutil
import subprocess
import re

def print_step(msg):
    print(f"\n[{'='*50}]")
    print(f"[+] {msg}")
    print(f"[{'='*50}]\n")

def main():
    root_dir = os.path.dirname(os.path.abspath(__file__))
    backend_dir = os.path.abspath(os.path.join(root_dir, "..", "Memora_Web"))
    
    # 1. Ask for the new version
    print_step("Pembaruan Versi Aplikasi Memora")
    new_version = input("Masukkan versi baru (contoh: 1.0.2): ").strip()
    if not new_version:
        print("Versi tidak boleh kosong!")
        sys.exit(1)

    # Validate version format (e.g. 1.0.2)
    if not re.match(r"^\d+\.\d+\.\d+$", new_version):
        print("Format versi tidak valid! Gunakan format X.Y.Z (contoh: 1.0.2)")
        sys.exit(1)

    # 2. Update version and build number in pubspec.yaml
    pubspec_path = os.path.join(root_dir, "pubspec.yaml")
    if os.path.exists(pubspec_path):
        with open(pubspec_path, "r", encoding="utf-8") as f:
            content = f.read()
        
        # Match "version: X.Y.Z+W"
        match = re.search(r"^version:\s*([0-9\.]+)\+([0-9]+)", content, re.MULTILINE)
        if match:
            current_build_num = int(match.group(2))
            new_build_num = current_build_num + 1
            new_version_string = f"version: {new_version}+{new_build_num}"
            content = re.sub(r"^version:\s*[^\r\n]+", new_version_string, content, flags=re.MULTILINE)
            with open(pubspec_path, "w", encoding="utf-8") as f:
                f.write(content)
            print(f"SUCCESS: Diperbarui pubspec.yaml ke versi {new_version}+{new_build_num}")
        else:
            print("WARNING: Format versi di pubspec.yaml tidak cocok, dilewati.")

    # 3. Update version in AppConstants.dart (optional fallback)
    app_constants_path = os.path.join(root_dir, "lib", "core", "constants", "app_constants.dart")
    if os.path.exists(app_constants_path):
        with open(app_constants_path, "r", encoding="utf-8") as f:
            content = f.read()
        
        new_content = re.sub(
            r"static const String currentAppVersion = '[^']+';",
            f"static const String currentAppVersion = '{new_version}';",
            content
        )
        with open(app_constants_path, "w", encoding="utf-8") as f:
            f.write(new_content)
        print(f"SUCCESS: Diperbarui AppConstants.dart ke versi {new_version}")

    # 4. Update version in routes/api.php (optional fallback)
    api_routes_path = os.path.join(backend_dir, "routes", "api.php")
    if os.path.exists(api_routes_path):
        with open(api_routes_path, "r", encoding="utf-8") as f:
            content = f.read()
        
        content = re.sub(
            r"'latest_version'\s*=>\s*'[^']+'",
            f"'latest_version' => '{new_version}'",
            content
        )
        with open(api_routes_path, "w", encoding="utf-8") as f:
            f.write(content)
        print(f"SUCCESS: Diperbarui routes/api.php ke versi {new_version}")

    # 5. Update version in welcome.blade.php
    welcome_path = os.path.join(backend_dir, "resources", "views", "welcome.blade.php")
    if os.path.exists(welcome_path):
        with open(welcome_path, "r", encoding="utf-8") as f:
            welcome_content = f.read()
        
        # Match asset('downloads/memora-latest.apk') and its download attribute
        new_welcome_content = re.sub(
            r"(href=\"\{\{\s*asset\('downloads/memora-latest\.apk'\)\s*\}\}\")(?:\s+download=\"[^\"]+\")?",
            f"\\g<1> download=\"Memora-v{new_version}.apk\"",
            welcome_content
        )
        
        with open(welcome_path, "w", encoding="utf-8") as f:
            f.write(new_welcome_content)
        print(f"SUCCESS: Diperbarui penamaan download APK di welcome.blade.php ke Memora-v{new_version}.apk")
    
    # 6. Build APK Release
    print_step("Membangun APK Release")
    try:
        subprocess.run(["flutter", "build", "apk", "--release"], cwd=root_dir, check=True, shell=True)
    except subprocess.CalledProcessError:
        print("ERROR: Gagal membangun APK!")
        sys.exit(1)

    # 7. Copy APK to backend public/downloads
    print_step("Menyalin APK ke Backend")
    source_apk = os.path.join(root_dir, "build", "app", "outputs", "flutter-apk", "app-release.apk")
    target_apk = os.path.join(backend_dir, "public", "downloads", "memora-latest.apk")
    
    if not os.path.exists(source_apk):
        print(f"ERROR: File APK tidak ditemukan di: {source_apk}")
        sys.exit(1)
        
    os.makedirs(os.path.dirname(target_apk), exist_ok=True)
    shutil.copy2(source_apk, target_apk)
    print(f"SUCCESS: Berhasil menyalin APK ke {target_apk}")

    # 8. Deploy via FTP
    print_step("Mengirim ke Server via FTP")
    try:
        subprocess.run(["npm", "run", "deploy"], cwd=backend_dir, check=True, shell=True)
    except subprocess.CalledProcessError:
        print("ERROR: Gagal mengunggah ke FTP!")
        sys.exit(1)
        
    print_step("SUCCESS: PROSES DEPLOYMENT SELESAI!")
    print(f"Versi {new_version} telah berhasil di-build dan diunggah ke server.")

if __name__ == "__main__":
    main()
