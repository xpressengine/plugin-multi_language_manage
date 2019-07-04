<div class="row">
    <div class="col-sm-12">
        <div class="panel-group">
            <div class="panel">
                <form class="search_form" method="get" action="{{route('multi_language_manage::index')}}">
                    <div class="panel-heading">
                        <div class="pull-right">
                            <button type="button" data-url="{{route('multi_language_manage::export')}}" class="__export_btn xe-btn xe-btn-positive">내보내기</button>
                            <a href="#" data-url="{{route('multi_language_manage::get_import')}}" class="__import_btn xe-btn xe-btn-danger">가져오기</a>
                        </div>
                    </div>

                    <div class="panel-body">
                        <div class="col-sm-6">
                            <div class="xe-form-group">
                                <label>locale</label>
                                <select class="xe-form-control" name="locale">
                                    <option value="" @if (\Request::get('locale', '') == '') selected @endif>전체</option>
                                    @foreach (config('xe.lang.locales') as $idx => $locale)
                                        <option class="form-control" value="{{$locale}}" @if (\Request::get('locale') == $locale) selected @endif>{{$locale}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="xe-form-group">
                                <label>namespace</label>
                                <input type="text" name="namespace" class="xe-form-control" value="{{\Request::get('namespace')}}">
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="xe-form-group">
                                <label>item</label>
                                <input type="text" name="item" class="xe-form-control" value="{{\Request::get('item')}}">
                            </div>

                            <div class="xe-form-group">
                                <label>value</label>
                                <input type="text" name="value" class="xe-form-control" value="{{\Request::get('value')}}">
                            </div>
                        </div>

                        <button type="submit" data-url="{{route('multi_language_manage::index')}}" class="__search_btn xe-btn text-btn">검색</button>
                    </div>
                </form>
            </div>

            <div class="panel">
                <div class="panel-body">
                    <table class="xe-table xe-table-hover">
                        <thead>
                        <tr>
                            <th>namespace</th>
                            <th>item</th>
                            @foreach ($locales as $locale => $idx)
                                <th>{{$locale}}</th>
                            @endforeach
                        </tr>
                        </thead>

                        <tbody>
                        @foreach ($langs as $lang)
                        <tr>
                            <td>{{$lang['namespace']}}</td>
                            <td>{{$lang['item']}}</td>

                            @foreach ($locales as $locale => $idx)
                                <td>{{$lang[$locale]}}</td>
                            @endforeach
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                {!! $langs->render() !!}
            </div>
        </div>
    </div>
</div>

<script>
    $(function () {
        $('.__search_btn').click(function (e) {
            e.preventDefault();
            $(this).closest('form').attr('action', $(this).data('url'));
            $(this).closest('form').submit();
        });

        $('.__export_btn').click(function (e) {
            $(this).closest('form').attr('action', $(this).data('url'));
            $(this).closest('form').submit();
        })

        $('.__import_btn').click(function () {
            var url = $(this).data('url');
            window.XE.pageModal(url);
        });
    })
</script>
