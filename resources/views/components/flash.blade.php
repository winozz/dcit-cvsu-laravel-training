@if(session('status'))
    <div style="margin:8px 0 16px;padding:10px 12px;border:2px solid #57f287;color:#57f287;background:#0f1735;border-radius:8px;">
        {{ session('status') }}
    </div>
@endif
