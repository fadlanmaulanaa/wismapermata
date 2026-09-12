<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto dismiss alerts
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(a => {
        let b = bootstrap.Alert.getOrCreateInstance(a);
        if(b) b.close();
    });
}, 4000);
</script>
</body>
</html>
