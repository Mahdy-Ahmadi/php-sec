<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ثبت نام | سیستم امن PHP</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>ثبت نام در سیستم</h1>
            
            <div id="error-message" class="alert alert-danger" style="display: none;"></div>
            
            <form id="register-form" method="POST" action="/register/submit">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label for="name">نام کامل</label>
                    <input type="text" id="name" name="name" required minlength="3" maxlength="50">
                </div>
                
                <div class="form-group">
                    <label for="email">ایمیل</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">رمز عبور</label>
                    <input type="password" id="password" name="password" required>
                    <small>حداقل 12 کاراکتر، شامل حروف بزرگ، کوچک، عدد و کاراکتر خاص</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">تکرار رمز عبور</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">ثبت نام</button>
            </form>
            
            <div class="links">
                <a href="/login">قبلاً ثبت نام کرده‌اید؟ وارد شوید</a>
            </div>
        </div>
    </div>
    
    <script>
        const password = document.getElementById('password');
        const confirm = document.getElementById('confirm_password');
        
        function validatePassword() {
            if (password.value !== confirm.value) {
                confirm.setCustomValidity("رمزهای عبور مطابقت ندارند");
            } else {
                confirm.setCustomValidity('');
            }
        }
        
        password.onchange = validatePassword;
        confirm.onkeyup = validatePassword;
        
        document.getElementById('register-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const response = await fetch('/register/submit', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert(result.message);
                window.location.href = '/login';
            } else if (result.errors) {
                const errorDiv = document.getElementById('error-message');
                errorDiv.innerHTML = result.errors.join('<br>');
                errorDiv.style.display = 'block';
            } else if (result.error) {
                const errorDiv = document.getElementById('error-message');
                errorDiv.textContent = result.error;
                errorDiv.style.display = 'block';
            }
        });
    </script>
</body>
</html>
