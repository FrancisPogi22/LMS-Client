function errorMessage(message) {
  Swal.fire({
    title: "Error!",
    text: message,
    icon: "error",
  });
}

function warningMessage(message) {
  Swal.fire({
    title: message,
    icon: "warning",
    cancelButtonText: "Close",
  });
}

function successMessage(message) {
  Swal.fire({
    title: message,
    icon: "success",
    confirmButtonText: "Close",
  });
}
