<div class="row">
    <div class="col-sm-12">
        <div class="panel-group">
            <div class="panel">
                <form method="get">
                    <div class="form-group">
                        <div class="panel-heading">
                            <button type="button" data-url="{{route('multi_language_manage::export')}}" class="__submit_btn xe-btn xe-btn-positive">내보내기</button>
                        </div>

                        <div class="panel-body">
                            <label>namespace</label>
                            <input type="text" name="namespace" value="{{\Request::get('namespace')}}">

                            <label>locale</label>
                            <select name="locale">
                                <option value="" @if (\Request::get('locale', '') == '') selected @endif>전체</option>
                                @foreach (config('xe.lang.locales') as $idx => $locale)
                                    <option class="form-control" value="{{$locale}}" @if (\Request::get('locale') == $locale) selected @endif>{{$locale}}</option>
                                @endforeach
                            </select>

                            <label>value</label>
                            <input type="text" name="value" value="{{\Request::get('value')}}">

                            <button type="button" data-url="{{route('multi_language_manage::index')}}" class="__submit_btn xe-btn text-btn">검색</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="panel">
                <div class="panel-body">
                    <table class="xe-table xe-table-hover">
                        <thead>
                        <tr>
                            <th>namespace</th>
                            <th>key</th>
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
        $('.__submit_btn').click(function (e) {
            $(this).closest('form').attr('action', $(this).data('url'));
            alert('hi');
            $(this).closest('form').submit();
        })
    })
</script>
