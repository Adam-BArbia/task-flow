import './stimulus_bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

document.addEventListener('DOMContentLoaded', () => {
	document.querySelectorAll('.delete-btn').forEach((button) => {
		button.addEventListener('click', (event) => {
			event.preventDefault();

			const form = button.closest('.delete-form');
			if (!form || typeof Swal === 'undefined') {
				return;
			}

			Swal.fire({
				title: 'Êtes-vous sûr ?',
				text: 'Cette action est irréversible !',
				icon: 'warning',
				showCancelButton: true,
				confirmButtonColor: '#dc3545',
				cancelButtonColor: '#6c757d',
				confirmButtonText: 'Oui, supprimer',
				cancelButtonText: 'Annuler',
				reverseButtons: true,
			}).then((result) => {
				if (result.isConfirmed) {
					form.submit();
				}
			});
		});
	});
});
