<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — {{ config('daya.engine_name', 'CEGU') }}</title>
    <style>
        body{margin:0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;background:#0f172a;color:#0f172a;display:flex;min-height:100vh;align-items:center;justify-content:center}
        .box{background:#fff;border-radius:14px;padding:32px;width:340px;box-shadow:0 20px 50px rgba(0,0,0,.3)}
        h1{font-size:1.3rem;margin:0 0 4px}.sub{color:#64748b;font-size:.85rem;margin-bottom:20px}
        label{display:block;font-size:.85rem;font-weight:600;margin:12px 0 4px}
        input{width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:8px;font:inherit;box-sizing:border-box}
        .btn{margin-top:18px;width:100%;background:#1f6feb;color:#fff;border:0;border-radius:8px;padding:11px;font-weight:600;cursor:pointer}
        .err{background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:9px 12px;border-radius:8px;font-size:.85rem;margin-bottom:12px}
        .chk{display:flex;align-items:center;gap:8px;margin-top:12px;font-size:.85rem}.chk input{width:auto}
    </style>
</head>
<body>
    <form class="box" method="POST" action="{{ route('login') }}">
        @csrf
        <h1>{{ config('daya.engine_name', 'CEGU') }}</h1>
        <div class="sub">Masuk ke panel admin</div>
        @if($errors->any())<div class="err">{{ $errors->first() }}</div>@endif
        <label>Email</label>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus>
        <label>Password</label>
        <input type="password" name="password" required>
        <label class="chk"><input type="checkbox" name="remember" value="1"> Ingat saya</label>
        <button class="btn">Masuk</button>
    </form>
</body>
</html>
