/**
 * Admin Dashboard Interactivity & Media Cleaner Engine
 */

document.addEventListener('DOMContentLoaded', () => {
    initMediaCleaner();
    initPostEditor();
});

function initMediaCleaner() {
    const selectAllCheckbox = document.getElementById('select-all-media');
    const mediaCheckboxes = document.querySelectorAll('.media-select-cb');
    const batchDeleteBtn = document.getElementById('batch-delete-btn');
    const cleanAllOrphansBtn = document.getElementById('clean-all-orphans-btn');

    // 全选/取消全选
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', () => {
            mediaCheckboxes.forEach(cb => cb.checked = selectAllCheckbox.checked);
            updateBatchButtonState();
        });
    }

    mediaCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateBatchButtonState);
    });

    function updateBatchButtonState() {
        const checkedCount = document.querySelectorAll('.media-select-cb:checked').length;
        if (batchDeleteBtn) {
            batchDeleteBtn.disabled = (checkedCount === 0);
            batchDeleteBtn.innerText = `批量删除选中项 (${checkedCount})`;
        }
    }

    // 批量删除
    if (batchDeleteBtn) {
        batchDeleteBtn.addEventListener('click', async () => {
            const checked = Array.from(document.querySelectorAll('.media-select-cb:checked')).map(cb => cb.value);
            if (checked.length === 0) return;

            if (!confirm(`确定要永久删除选中的 ${checked.length} 个附件及其物理文件吗？此操作不可逆！`)) {
                return;
            }

            batchDeleteBtn.disabled = true;
            batchDeleteBtn.innerText = '正在清理...';

            try {
                const formData = new FormData();
                formData.append('ids', checked.join(','));

                const res = await fetch('/admin/media/delete-batch', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    alert(`清理完成！成功删除 ${data.result.deleted} 个文件，释放存储空间 ${data.result.freed_formatted}`);
                    window.location.reload();
                } else {
                    alert('清理失败：' + (data.error || '未知错误'));
                }
            } catch (err) {
                alert('网络请求失败');
            }
        });
    }

    // --- 磁盘模式多选与批量删除 ---
    const selectAllDiskCb = document.getElementById('select-all-disk-media');
    const diskMediaCbs = document.querySelectorAll('.disk-media-select-cb');
    const diskBatchDelBtn = document.getElementById('disk-batch-delete-btn');

    if (selectAllDiskCb) {
        selectAllDiskCb.addEventListener('change', () => {
            diskMediaCbs.forEach(cb => cb.checked = selectAllDiskCb.checked);
            updateDiskBatchBtn();
        });
    }

    diskMediaCbs.forEach(cb => {
        cb.addEventListener('change', updateDiskBatchBtn);
    });

    function updateDiskBatchBtn() {
        const checked = document.querySelectorAll('.disk-media-select-cb:checked');
        if (diskBatchDelBtn) {
            diskBatchDelBtn.disabled = checked.length === 0;
            diskBatchDelBtn.innerText = `批量删除选中磁盘文件 (${checked.length})`;
        }
    }

    if (diskBatchDelBtn) {
        diskBatchDelBtn.addEventListener('click', async () => {
            const checked = Array.from(document.querySelectorAll('.disk-media-select-cb:checked')).map(cb => cb.value);
            if (checked.length === 0) return;
            if (!confirm(`确定彻底从物理磁盘删除选中的 ${checked.length} 个文件？此操作不可逆！`)) return;

            diskBatchDelBtn.disabled = true;
            diskBatchDelBtn.innerText = '正在物理删除...';

            const form = new FormData();
            checked.forEach(path => form.append('paths[]', path));

            try {
                const res = await fetch('/admin/media/disk-delete-batch', { method: 'POST', body: form });
                const data = await res.json();
                if (data.success) {
                    alert(`成功从磁盘删除 ${data.result.deleted} 个文件，释放存储空间 ${data.result.freed_formatted}`);
                    window.location.reload();
                } else {
                    alert('删除失败');
                    diskBatchDelBtn.disabled = false;
                }
            } catch (err) {
                alert('网络请求失败');
                diskBatchDelBtn.disabled = false;
            }
        });
    }

    // --- 一键物理清理磁盘所有孤立文件 ---
    const cleanAllDiskOrphansBtn = document.getElementById('clean-all-disk-orphans-btn');
    if (cleanAllDiskOrphansBtn) {
        cleanAllDiskOrphansBtn.addEventListener('click', async () => {
            if (!confirm('警告：此操作将全盘遍历物理目录，彻底删除所有【未被任何文章引用】的孤立文件并释放磁盘空间！是否继续？')) return;

            cleanAllDiskOrphansBtn.disabled = true;
            cleanAllDiskOrphansBtn.innerHTML = '正在深度全盘清理中...';

            try {
                const res = await fetch('/admin/media/disk-clean-orphans', { method: 'POST' });
                const data = await res.json();
                if (data.success) {
                    alert(`磁盘清理完毕！共删除 ${data.result.deleted} 个冗余孤立文件，释放空间 ${data.result.freed_formatted}`);
                    window.location.reload();
                } else {
                    alert('清理失败');
                    cleanAllDiskOrphansBtn.disabled = false;
                }
            } catch (err) {
                alert('请求异常');
                cleanAllDiskOrphansBtn.disabled = false;
            }
        });
    }

    // 一键清理全部未引用孤立附件
    if (cleanAllOrphansBtn) {
        cleanAllOrphansBtn.addEventListener('click', async () => {
            if (!confirm('警告：将扫描全站并批量删除所有【引用数为 0】的孤立文件！\n\n确定执行一键清理吗？')) {
                return;
            }

            cleanAllOrphansBtn.disabled = true;
            cleanAllOrphansBtn.innerText = '正在全站扫描与清理...';

            try {
                const res = await fetch('/admin/media/clean-orphans', { method: 'POST' });
                const data = await res.json();
                if (data.success) {
                    alert(`一键清理完成！共清理 ${data.result.deleted} 个无用附件，成功释放空间 ${data.result.freed_formatted}`);
                    window.location.reload();
                } else {
                    alert('清理失败');
                }
            } catch (e) {
                alert('请求异常');
            } finally {
                cleanAllOrphansBtn.disabled = false;
                cleanAllOrphansBtn.innerText = '一键清理所有未引用文件 🧹';
            }
        });
    }
}

function initPostEditor() {
    const postForm = document.getElementById('post-edit-form');
    if (!postForm) return;

    postForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        // 同步 UEditor 内容
        if (window.ueEditor) {
            const editorContent = window.ueEditor.getContent();
            const textarea = postForm.querySelector('textarea[name="content"]') || postForm.querySelector('#post-content-editor');
            if (textarea) textarea.value = editorContent;
        }

        const submitBtn = postForm.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerText = '保存中...';

        const formData = new FormData(postForm);
        // 确保 content 字段包含 UEditor 的 HTML
        if (window.ueEditor) {
            formData.set('content', window.ueEditor.getContent());
        }

        try {
            const res = await fetch('/admin/posts/save', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                alert('文章保存成功！');
                window.location.href = '/admin/posts';
            } else {
                alert('保存失败：' + (data.error || ''));
            }
        } catch (e) {
            alert('保存异常');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerText = '保存文章';
        }
    });
}

async function uploadAndInsertImage(file, textarea) {
    const formData = new FormData();
    formData.append('file', file);

    const placeholder = `\n![上传中...]()\n`;
    const startPos = textarea.selectionStart;
    textarea.value = textarea.value.substring(0, startPos) + placeholder + textarea.value.substring(textarea.selectionEnd);

    try {
        const res = await fetch('/admin/upload', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success && data.url) {
            const imgTag = `<p><img src="${data.url}" alt="${file.name || 'image'}"></p>`;
            textarea.value = textarea.value.replace(placeholder, imgTag);
        } else {
            textarea.value = textarea.value.replace(placeholder, '');
            alert('图片上传失败');
        }
    } catch (e) {
        textarea.value = textarea.value.replace(placeholder, '');
        alert('上传请求异常');
    }
}
