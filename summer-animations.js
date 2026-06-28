document.addEventListener('DOMContentLoaded', function () {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    document.body.classList.add('summer-ready');

    const animatedElements = document.querySelectorAll('h1, h2, nav, table, body > p, .question-bloc, .correction-bloc, #ecran-demarrage, #ecran-qcm');
    animatedElements.forEach(function (element, index) {
        element.style.setProperty('--summer-delay', Math.min(index * 70, 560) + 'ms');
        element.classList.add('summer-reveal');
    });

    const particles = document.createElement('div');
    particles.className = 'summer-particles';
    particles.setAttribute('aria-hidden', 'true');

    const types = ['sun', 'wave', 'spark'];
    for (let index = 0; index < 18; index++) {
        const particle = document.createElement('span');
        particle.className = 'summer-particle summer-particle-' + types[index % types.length];
        particle.style.setProperty('--x', Math.random() * 100 + 'vw');
        particle.style.setProperty('--size', (8 + Math.random() * 14) + 'px');
        particle.style.setProperty('--duration', (11 + Math.random() * 10) + 's');
        particle.style.setProperty('--delay', (-Math.random() * 12) + 's');
        particle.style.setProperty('--drift', (-35 + Math.random() * 70) + 'px');
        particles.appendChild(particle);
    }

    document.body.appendChild(particles);

    document.querySelectorAll('a, button').forEach(function (element) {
        element.addEventListener('pointermove', function (event) {
            const rect = element.getBoundingClientRect();
            const x = ((event.clientX - rect.left) / rect.width) * 100;
            const y = ((event.clientY - rect.top) / rect.height) * 100;
            element.style.setProperty('--shine-x', x + '%');
            element.style.setProperty('--shine-y', y + '%');
        });
    });

    if (typeof window.demarrerQcm === 'function') {
        const demarrerQcmOriginal = window.demarrerQcm;
        window.demarrerQcm = function () {
            demarrerQcmOriginal.apply(this, arguments);

            window.requestAnimationFrame(function () {
                const ecranQcm = document.getElementById('ecran-qcm');
                if (!ecranQcm) {
                    return;
                }

                ecranQcm.classList.remove('summer-reveal');
                ecranQcm.offsetHeight;
                ecranQcm.classList.add('summer-reveal');
            });
        };
    }
});
