/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import 'bootstrap/dist/css/bootstrap.min.css';
import './styles/app.css';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

const refreshForm = document.querySelector('#github-refresh-form');
const refreshButton = document.querySelector('.btn-refresh');

refreshForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const refreshLink = refreshForm.getAttribute('action');
    const originalButtonText = refreshButton.innerHTML;
    refreshButton.innerHTML = 'Refreshing...';
    refreshButton.disabled = true;

    const response = await fetch(refreshLink, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': refreshForm.querySelector('input[name="_token"]').value,
        },
    });
    const data = await response.json();
    if (response.ok) {
        refreshButton.innerHTML = `Refreshed! (${data.count} repos)`;
        refreshButton.classList.remove('btn-primary');
        refreshButton.classList.add('btn-success');
    } else {
        refreshButton.innerHTML = 'Refresh Failed';
        refreshButton.classList.remove('btn-primary');
        refreshButton.classList.add('btn-danger');
    }

    setTimeout(() => {
        refreshButton.innerHTML = originalButtonText;
        refreshButton.classList.remove('btn-success', 'btn-danger');
        refreshButton.classList.add('btn-primary');
    }, 3000);

    refreshButton.disabled = false;
});