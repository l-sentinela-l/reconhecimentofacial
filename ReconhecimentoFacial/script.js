// Em cadastrar.js
document.querySelector('input[name="cpf"]').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    
    if (value.length > 3) {
        value = value.substring(0, 3) + '.' + value.substring(3);
    }
    if (value.length > 7) {
        value = value.substring(0, 7) + '.' + value.substring(7);
    }
    if (value.length > 11) {
        value = value.substring(0, 11) + '-' + value.substring(11, 13);
    }
    
    e.target.value = value;
});