<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Número</th>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Total</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($cotizacion = $result->fetchArray(SQLITE3_ASSOC)): ?>
                <tr>
                    <td><?= htmlspecialchars($cotizacion['numero']) ?></td>
                    <td><?= date('d/m/Y', strtotime($cotizacion['fecha'])) ?></td>
                    <td>
                        <?= htmlspecialchars($cotizacion['cliente_nombre']) ?>
                        <br>
                        <small class="text-muted"><?= htmlspecialchars($cotizacion['cliente_rut']) ?></small>
                    </td>
                    <td>$<?= number_format($cotizacion['total'], 2) ?></td>
                    <td>
                        <?php if ($cotizacion['estado'] === 'Pendiente'): ?>
                            <span class="badge bg-warning">Pendiente</span>
                        <?php else: ?>
                            <span class="badge bg-success">Procesada</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="ver_cotizacion.php?id=<?= $cotizacion['id'] ?>" class="btn btn-primary btn-sm">Ver</a>
                        <?php if ($cotizacion['estado'] === 'Pendiente'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="cotizacion_id" value="<?= $cotizacion['id'] ?>">
                                <button type="submit" class="btn btn-success btn-sm">Procesar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div> 