const API_URL = 'api/comments.php';

document.addEventListener('DOMContentLoaded', () => {
    loadComments();
    setupForm();
});

function setupForm() {
    const form = document.getElementById('commentForm');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const author = document.getElementById('author').value.trim();
        const content = document.getElementById('content').value.trim();
        
        if (!author || !content) {
            showMessage('Por favor completá todos los campos', 'error');
            return;
        }
        
        try {
            const response = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ author, content })
            });
            
            const data = await response.json();
            
            if (data.success) {
                document.getElementById('commentForm').reset();
                showMessage('Comentario publicado correctamente', 'success');
                loadComments();
            } else {
                showMessage(data.error || 'Error al publicar', 'error');
            }
        } catch (error) {
            showMessage('Error de conexión', 'error');
        }
    });
}

async function loadComments() {
    const container = document.getElementById('commentsContainer');
    
    try {
        const response = await fetch(API_URL);
        const data = await response.json();
        
        if (data.success && data.comments.length > 0) {
            document.getElementById('commentCount').textContent = `(${data.comments.length})`;
            container.innerHTML = data.comments.map(comment => `
                <div class="comment-card" data-id="${comment.id}">
                    <div class="comment-header">
                        <span class="comment-author">${escapeHtml(comment.author)}</span>
                        <span class="comment-date">${formatDate(comment.created_at)}</span>
                    </div>
                    <div class="comment-content">${escapeHtml(comment.content)}</div>
                </div>
            `).join('');
        } else {
            document.getElementById('commentCount').textContent = '(0)';
            container.innerHTML = '<p class="empty-state">No hay comentarios todavía. ¡Sé el primero en comentar!</p>';
        }
    } catch (error) {
        container.innerHTML = '<p class="error-message">Error al cargar comentarios</p>';
    }
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-AR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showMessage(text, type) {
    const existing = document.querySelector('.error-message, .success-message');
    if (existing) existing.remove();
    
    const msg = document.createElement('div');
    msg.className = type === 'error' ? 'error-message' : 'success-message';
    msg.textContent = text;
    
    const form = document.querySelector('.comment-form');
    form.insertBefore(msg, form.firstChild);
    
    setTimeout(() => msg.remove(), 3000);
}
