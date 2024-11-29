
**Server Setup**

1. **Update `.gitignore` file**:  
   Add the following entries to the `.gitignore` file: 

   ```
   *.zip
   .htaccess
   server.php
   /public/uploads
   ```

2. **Remove `.gitignore` content for `public/assets` folder**:  
   Ensure that only the following rule is removed from the `public/assets` folder: 

   ```
   *.txt
   ```

3. **Connect Git to the server**:  
   Connect the Git repository to the server and pull all the code.

4. **Upload additional files and folders**:  
   After pulling the code, manually upload the following files and folders to the server:  

   - `.env`  
   - `.htaccess`  
   - `server.php`  
   - `vendor` (folder)

5. **Edit `server.php` file**:  
   Add the following line at the bottom of the `server.php` file:  

   ```php
   require_once __DIR__ . '/public/index.php';
   ```

6. **Modify the helper file**:  
   Edit the following functions in the helper file as needed:  

   - `getFileBaseURL`  
   - `static_asset`  
   - `my_asset`

---

 **Local Setup**

1. **Pull the code from Git**:  
   Clone or pull all the code from the Git repository.

2. **Add required files to the root directory**:  
   Place the following files and folders in the root directory:  
   - `server.php`  
   - `vendor` (folder)  
   - `.env`  
   - `/public/uploads` (uploads folder)

3. **Update `.env` variable**:  
   Change the `APP_ENV` variable in the `.env` file to:  
   ```
   APP_ENV=local
   ```

4. **Edit the `server.php` file**:  
   Add the following line at the bottom of the `server.php` file:  
   ```php
   require_once __DIR__ . '/index.php';
   ```
