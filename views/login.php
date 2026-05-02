<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود | سیستم امن PHP</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <h1> ورود به سیستم</h1>
            
            <div id="error-message" class="alert alert-danger" style="display: none;"></div>
            <div id="success-message" class="alert alert-success" style="display: none;"></div>
            
            <form id="login-form" method="POST" action="/login/submit">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label for="email">📧 ایمیل</label>
                    <input type="email" id="email" name="email" required autocomplete="email">
                </div>
                
                <div class="form-group">
                    <label for="password">رمز عبور</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>
                
                <button type="submit" class="btn btn-primary">ورود</button>
            </form>
            
            <div class="links">
                <a href="/register">حساب کاربری ندارید؟ ثبت نام کنید</a>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('login-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const response = await fetch('/login/submit', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                if (result.requires_2fa) {
                    window.location.href = '/2fa';
                } else {
                    window.location.href = result.redirect;
                }
            } else if (result.error) {
                const errorDiv = document.getElementById('error-message');
                errorDiv.textContent = result.error;
                errorDiv.style.display = 'block';
                setTimeout(() => {
                    errorDiv.style.display = 'none';
                }, 5000);
            }
        });
    </script>
</body>
</html>
