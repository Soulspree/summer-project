 
<?php
define('SYSTEM_ACCESS', true);
require_once __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Musicians - <?php echo APP_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .search-wrapper {
            max-width: 700px;
            margin: 4rem auto;
        }
        .form-control {
            border-radius: 50px;
            padding: 1rem 1.5rem;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 8px rgba(102,126,234,0.5);
        }
        .result-item {
            border-radius: 10px;
        }
    </style>
</head>
<body>
<div class="container search-wrapper">
    <input type="text" id="query" class="form-control form-control-lg" placeholder="Search musicians..." required>
    <div class="invalid-feedback" id="query-error"></div>
    <div id="results" class="mt-4"></div>
</div>

<script>
const queryInput = document.getElementById('query');
const resultsDiv = document.getElementById('results');
const errorDiv = document.getElementById('query-error');

function renderResults(list){
    resultsDiv.innerHTML = '';
    if (!list || !list.length){
        resultsDiv.innerHTML = '<p class="text-muted">No musicians found.</p>';
        return;
    }
    list.forEach(m => {
        const div = document.createElement('div');
        div.className = 'result-item p-3 mb-2 bg-white shadow-sm';
        const name = m.stage_name || [m.first_name, m.last_name].filter(Boolean).join(' ');
        div.textContent = name;
        resultsDiv.appendChild(div);
    });
}

queryInput.addEventListener('input', () => {
    const q = queryInput.value.trim();
    if (q.length < 2){
        queryInput.classList.add('is-invalid');
        errorDiv.textContent = 'Enter at least 2 characters';
        resultsDiv.innerHTML = '';
        return;
    }
    queryInput.classList.remove('is-invalid');
    errorDiv.textContent = '';
    fetch(`/api/search.php?q=${encodeURIComponent(q)}`)
        .then(res => res.json())
        .then(data => renderResults(data.musicians))
        .catch(() => {
            resultsDiv.innerHTML = '<p class="text-danger">Error fetching results.</p>';
        });
});
</script>
</body>
</html>
