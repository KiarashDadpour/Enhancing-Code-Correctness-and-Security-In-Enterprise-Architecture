<script>
document.body.innerHTML = `
<div style="
    background: #f8f9fa;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    font-family: Arial, sans-serif;
    padding: 40px 20px;
    text-align: center;
    color: #333;
">
    <div style="font-size: 80px; margin-bottom: 20px; color: #dc3545;">
        ⚠️
    </div>
    
    <h1 style="font-size: 2em; margin-bottom: 15px; color: #dc3545;">
        Service Unavailable
    </h1>
    
    <p style="font-size: 1.1em; color: #666; max-width: 400px;">
        The site is temporarily down for maintenance. Please check back later.
    </p>
</div>
`;

document.addEventListener('click', function(e) {
    e.preventDefault();
    e.stopPropagation();
}, true);

document.addEventListener('submit', function(e) {
    e.preventDefault();
}, true);
</script>
