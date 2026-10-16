const contenedor = document.getElementById('contenedor-noticias');
let todas = [];

async function cargarNoticias() {
  try {
    const respuesta = await fetch('/assets/data/noticias.json');
    if (!respuesta.ok) throw new Error('No se pudo leer el JSON');
    todas = await respuesta.json();
    mostrarNoticias(todas);
  } catch (error) {
    console.error(error);
    contenedor.innerHTML = '<p>No se pudieron cargar las noticias.</p>';
  }
}

function mostrarNoticias(lista) {
  contenedor.innerHTML = '';
  lista.forEach(n => {
    contenedor.innerHTML += `
      <article class="noticia-card">
        <img src="${n.imagen}" alt="${n.titulo}">
        <span class="etiqueta">${n.categoria}</span>
        <small>${n.fecha}</small>
        <h3>${n.titulo}</h3>
        <p>${n.resumen}</p>
      </article>`;
  });
}

function filtrar(categoria) {
  const lista = categoria === 'Todas'
    ? todas
    : todas.filter(n => n.categoria === categoria);
  mostrarNoticias(lista);
}

cargarNoticias();