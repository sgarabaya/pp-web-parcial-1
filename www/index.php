<?php
require_once "autoload.php";
require_once "./components/header.php";
?>

<div class="container">
    <section>
        <h2>Upload Image</h2>
        <form action="upload_image.php" method="post" enctype="multipart/form-data">
          Select image to upload:
          <input type="file" name="fileToUpload" id="fileToUpload">
          <input type="submit" value="Upload Image" name="submit">
        </form>
    </section>

    <hr>

    <section>
        <h2>Create User</h2>
        <form action="create_user.php" method="post">
            <div>
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div>
                <label for="lastName">Last Name:</label>
                <input type="text" id="lastName" name="lastName" required>
            </div>
            <div>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div>
                <label for="role">Role:</label>
                <select id="role" name="role">
                    <option value="SALES">SALES</option>
                    <option value="STOCK">STOCK</option>
                    <option value="ADMIN">ADMIN</option>
                </select>
            </div>
            <div>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Create User</button>
        </form>
    </section>
</div>

<?php require_once "./components/footer.php"; ?>
