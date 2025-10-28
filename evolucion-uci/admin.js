const CLAVE_ADMIN = "faroladmin2024";

document.addEventListener("DOMContentLoaded", () => {
  cargarUsuarios();
});

function toggleClave() {
  const claveInput = document.getElementById("nuevaClave");
  claveInput.type = claveInput.type === "password" ? "text" : "password";
}

function cargarUsuarios() {
  fetch("admin_api.php?action=list", {
    headers: { "X-Admin-Key": CLAVE_ADMIN }
  })
    .then(r => r.json())
    .then(data => {
      if (!data.success) return alert("Acceso denegado");
      const ul = document.getElementById("usuarios");
      ul.innerHTML = "";
      data.usuarios.forEach(u => {
        const li = document.createElement("li");
        li.innerHTML = `👤 ${u.usuario} — ID: ${u.id}
          <button onclick="eliminarUsuario(${u.id})" style="margin-left:10px; background:red; color:white; border:none; padding:4px 8px; border-radius:5px; cursor:pointer;">Eliminar</button>`;
        ul.appendChild(li);
      });
    });
}

function registrar(event) {
  const usuario = document.getElementById("nuevoUsuario").value.trim();
  const clave = document.getElementById("nuevaClave").value.trim();
  const resultado = document.getElementById("resultado");

  if (!usuario || !clave) {
    resultado.textContent = "Completa todos los campos.";
    return;
  }

  fetch("admin_api.php?action=create", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-Admin-Key": CLAVE_ADMIN
    },
    body: JSON.stringify({ usuario, clave })
  })
    .then(r => r.json())
    .then(data => {
      resultado.textContent = data.message;
      if (data.success) {
        document.getElementById("nuevoUsuario").value = "";
        document.getElementById("nuevaClave").value = "";
        cargarUsuarios(); // refrescar lista
      }
    });
}

function eliminarUsuario(id) {
  if (!confirm("¿Seguro que quieres eliminar este usuario?")) return;

  fetch("admin_api.php?action=delete", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-Admin-Key": CLAVE_ADMIN
    },
    body: JSON.stringify({ id })
  })
    .then(r => r.json())
    .then(data => {
      alert(data.message);
      if (data.success) cargarUsuarios();
    });
}