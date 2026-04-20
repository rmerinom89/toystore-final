document.addEventListener("DOMContentLoaded", function () {
  const producto = document.getElementById("id_producto");
  const cantidad = document.getElementById("cantidad");
  const montoVista = document.getElementById("monto_total_vista");
  const formCarrito = document.getElementById("formCarrito");

  if (!producto || !cantidad || !montoVista || !formCarrito) {
    return;
  }

  function obtenerPrecioSeleccionado() {
    const opcion = producto.options[producto.selectedIndex];
    if (!opcion) return 0;

    const precio = opcion.getAttribute("data-precio");
    return precio ? Number(precio) : 0;
  }

  function calcularTotal() {
    const precio = obtenerPrecioSeleccionado();
    const cant = Number(cantidad.value) || 0;
    const total = precio * cant;

    montoVista.value = "$" + total.toLocaleString("es-CL");
    return total;
  }

  function validarCarrito() {
    if (producto.value === "") {
      alert("Debes seleccionar un juguete.");
      producto.focus();
      return false;
    }

    if (cantidad.value.trim() === "") {
      alert("Debes ingresar una cantidad.");
      cantidad.focus();
      return false;
    }

    if (isNaN(cantidad.value) || Number(cantidad.value) <= 0) {
      alert("La cantidad debe ser un número mayor a 0.");
      cantidad.focus();
      return false;
    }

    return true;
  }

  producto.addEventListener("change", calcularTotal);
  cantidad.addEventListener("input", calcularTotal);

  formCarrito.addEventListener("submit", function (e) {
    if (!validarCarrito()) {
      e.preventDefault();
      return;
    }

    calcularTotal();
  });

  calcularTotal();
});