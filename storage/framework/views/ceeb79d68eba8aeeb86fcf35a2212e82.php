<?php $__env->startSection('title', 'Dashboard Generate'); ?>

<?php $__env->startSection('content'); ?>
    
    <?php if(! $onboarding['complete']): ?>
    <div class="card a-p">
        <div class="row">
            <h2 style="margin:0">Mulai dari sini — <?php echo e($onboarding['done_count']); ?>/<?php echo e($onboarding['total']); ?> langkah selesai</h2>
        </div>
        <div style="margin-top:12px;display:flex;flex-direction:column;gap:10px">
            <?php $__currentLoopData = $onboarding['steps']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $step): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div style="display:flex;gap:10px;align-items:flex-start;opacity:<?php echo e($step['done'] ? '.55' : '1'); ?>">
                    <span style="font-size:1.1rem;line-height:1.3"><?php echo e($step['done'] ? '✅' : '⬜'); ?></span>
                    <div>
                        <strong><?php echo e($step['title']); ?></strong>
                        <?php if(!$step['done'] && $step['url']): ?>
                            — <a href="<?php echo e($step['url']); ?>" <?php if(str_starts_with($step['url'],'http') && !str_contains($step['url'], request()->getHost())): ?> target="_blank" rel="noopener" <?php endif; ?>>kerjakan →</a>
                        <?php endif; ?>
                        <div class="muted" style="font-size:.85rem"><?php echo e($step['hint']); ?></div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
    <?php endif; ?>

    
    <?php if($gsc): ?>
    <div class="card a-info">
        <h2 style="margin:0 0 10px">Search Console <span class="muted" style="font-weight:400;font-size:.9rem">(28 hari)</span></h2>
        <?php if(isset($gsc['error'])): ?>
            <p class="muted" style="margin:0">Belum bisa mengambil data: <?php echo e($gsc['error']); ?></p>
        <?php else: ?>
            <div style="display:flex;gap:26px;flex-wrap:wrap">
                <div><div style="font-size:1.6rem;font-weight:700"><?php echo e(number_format($gsc['clicks'])); ?></div><div class="muted" style="font-size:.8rem">klik</div></div>
                <div><div style="font-size:1.6rem;font-weight:700"><?php echo e(number_format($gsc['impressions'])); ?></div><div class="muted" style="font-size:.8rem">impresi</div></div>
                <div><div style="font-size:1.6rem;font-weight:700"><?php echo e($gsc['ctr']); ?>%</div><div class="muted" style="font-size:.8rem">CTR</div></div>
                <div><div style="font-size:1.6rem;font-weight:700"><?php echo e($gsc['position']); ?></div><div class="muted" style="font-size:.8rem">posisi rata-rata</div></div>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    
    <div class="card a-ok">
        <div class="row">
            <h2 style="margin:0">Klik WhatsApp <span class="muted" style="font-weight:400;font-size:.9rem">(30 hari terakhir)</span></h2>
            <div class="right" style="display:flex;gap:18px;align-items:baseline">
                <div style="text-align:right"><div style="font-size:1.6rem;font-weight:700"><?php echo e(number_format($leads['total'])); ?></div><div class="muted" style="font-size:.8rem">total klik</div></div>
                <div style="text-align:right"><div style="font-size:1.6rem;font-weight:700"><?php echo e(number_format($leads['today'])); ?></div><div class="muted" style="font-size:.8rem">hari ini</div></div>
            </div>
        </div>
        <?php if($leads['total'] === 0): ?>
            <p class="muted" style="margin:10px 0 0">Belum ada klik tercatat. Pastikan halaman sudah dipublish dan sudah dikunjungi. Data akan muncul otomatis saat pengunjung menekan tombol WhatsApp.</p>
        <?php else: ?>
            <p class="muted" style="margin:10px 0 6px">Halaman paling menghasilkan chat — gandakan pola yang menang:</p>
            <table style="width:100%;border-collapse:collapse;font-size:.9rem">
                <thead><tr style="text-align:left;border-bottom:1px solid var(--border)"><th style="padding:6px 0">Halaman</th><th style="padding:6px 0;width:90px;text-align:right">Klik</th></tr></thead>
                <tbody>
                <?php $__currentLoopData = $leads['top']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr style="border-bottom:1px solid var(--border)">
                        <td style="padding:6px 0"><a href="<?php echo e(url($row->page_path)); ?>" target="_blank" rel="noopener"><?php echo e($row->page_path); ?></a></td>
                        <td style="padding:6px 0;text-align:right;font-weight:600"><?php echo e(number_format($row->total)); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    
    <div class="card">
        <div class="row">
            <h2 style="margin:0">
                Kesehatan Stok Konten
                <?php if($health['all_ok']): ?>
                    <span class="pill completed" style="margin-left:8px">SIAP GENERATE MASSAL</span>
                <?php else: ?>
                    <span class="pill failed" style="margin-left:8px">SKOR <?php echo e($health['score']); ?>% — TAMBAH STOK DULU</span>
                <?php endif; ?>
            </h2>
            <a class="btn sm right" href="<?php echo e(route('admin.content.index')); ?>">Tambah Variasi</a>
        </div>
        <?php if(! $health['all_ok']): ?>
            <p class="muted" style="margin:8px 0 4px">⚠️ Stok kalimat di bawah target = halaman terlihat kembar di mata Google → banyak ditolak indeks. Penuhi target sebelum menayangkan halaman dalam jumlah besar.</p>
        <?php endif; ?>
        <div style="margin-top:12px;display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px">
            <?php $__currentLoopData = $health['sections']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sec): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div>
                    <div style="display:flex;justify-content:space-between;font-size:.85rem">
                        <span><?php echo e($sec['label']); ?></span>
                        <strong style="color:<?php echo e($sec['ok'] ? '#1a9e55' : '#c0392b'); ?>"><?php echo e($sec['count']); ?>/<?php echo e($sec['target']); ?></strong>
                    </div>
                    <div style="background:#e8ebf2;border-radius:6px;height:8px;overflow:hidden;margin-top:4px">
                        <div style="width:<?php echo e($sec['percent']); ?>%;height:100%;background:<?php echo e($sec['ok'] ? '#1a9e55' : '#f0a03a'); ?>"></div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <div>
                <div style="display:flex;justify-content:space-between;font-size:.85rem">
                    <span>FAQ aktif</span>
                    <strong style="color:<?php echo e($health['faq']['ok'] ? '#1a9e55' : '#c0392b'); ?>"><?php echo e($health['faq']['count']); ?>/<?php echo e($health['faq']['target']); ?></strong>
                </div>
                <div style="background:#e8ebf2;border-radius:6px;height:8px;overflow:hidden;margin-top:4px">
                    <div style="width:<?php echo e($health['faq']['percent']); ?>%;height:100%;background:<?php echo e($health['faq']['ok'] ? '#1a9e55' : '#f0a03a'); ?>"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-4" style="margin-bottom:18px">
        <div class="stat"><div class="n"><?php echo e(number_format($stats['total'])); ?></div><div class="l">Total Halaman</div></div>
        <div class="stat"><div class="n"><?php echo e(number_format($stats['draft'])); ?></div><div class="l">Draft</div></div>
        <div class="stat"><div class="n"><?php echo e(number_format($stats['published'])); ?></div><div class="l">Published</div></div>
        <div class="stat"><div class="n"><?php echo e(number_format($generate['failed'])); ?></div><div class="l">Gagal</div></div>
    </div>

    <div class="card">
        <h2 style="margin-top:0">Progres Generate</h2>
        <div class="grid grid-4">
            <div><div class="muted">Baris CSV</div><strong><?php echo e(number_format($generate['total_rows'])); ?></strong></div>
            <div><div class="muted">Sedang diproses</div><strong><?php echo e(number_format($generate['processing'])); ?></strong></div>
            <div><div class="muted">Selesai diproses</div><strong><?php echo e(number_format($generate['done'])); ?></strong></div>
            <div><div class="muted">Publish Queue</div><span class="pill <?php echo e($publishState); ?>"><?php echo e($publishState); ?></span></div>
        </div>
    </div>

    <div class="card">
        <div class="row">
            <h2 style="margin:0">Batch Import Terakhir</h2>
            <a class="btn sm right" href="<?php echo e(route('admin.imports.index')); ?>">Kelola Import</a>
        </div>
        <table style="margin-top:12px">
            <thead><tr><th>File</th><th>Status</th><th>Progress</th><th>Generated</th><th>Gagal</th></tr></thead>
            <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $batches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td class="mono"><?php echo e($b->original_filename); ?></td>
                    <td><span class="pill <?php echo e($b->status); ?>"><?php echo e($b->status); ?></span></td>
                    <td><?php echo e(number_format($b->processed_rows)); ?> / <?php echo e(number_format($b->total_rows)); ?></td>
                    <td><?php echo e(number_format($b->generated_count)); ?></td>
                    <td><?php echo e(number_format($b->failed_count)); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="5" class="muted">Belum ada import. <a href="<?php echo e(route('admin.imports.index')); ?>">Upload CSV pertama →</a></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Project\CEGU.CO.ID\DAYA AI ENGINE\daya-ai-engine\seo-main\resources\views/admin/dashboard.blade.php ENDPATH**/ ?>