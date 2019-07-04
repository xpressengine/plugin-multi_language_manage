<form action="{{ route('multi_language_manage::post_import') }}" method="post" enctype="multipart/form-data">
    {{ csrf_field() }}
    <div class="xe-modal-header">
        <button type="button" class="btn-close" data-dismiss="xe-modal" aria-label="Close"><i class="xi-close"></i></button>
        <strong class="xe-modal-title">다국어 업로드</strong>
    </div>
    <div class="xe-modal-body">
        <div>
            <p>- XE3에서 제공하는 엑셀 양식을 다운로드 후 양식에 맞게 입력해주세요.</p>
            <p>- 설정하고 싶은 언어의 국가 코드를 먼저 확인 해주세요.</p>
            <input type="file" class="xe-form-control" name="uploaded_file" accept=".xlsx">
            <input type="checkbox" name="is_force" value="1"> 기존 데이터를 덮어 씌우려면 체크하세요.
        </div>
    </div>
    <div class="xe-modal-footer">
        <button type="button" class="xe-btn xe-btn-secondary" data-dismiss="xe-modal">{{ xe_trans('xe::cancel') }}</button>
        <button type="submit" class="xe-btn xe-btn-primary" >{{ xe_trans('xe::apply') }}</button>
    </div>
</form>
