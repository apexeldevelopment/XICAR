<nav class="site-navbar navbar navbar-default navbar-fixed-top navbar-mega navbar-inverse bg-indigo-600"
     role="navigation">
  <div class="navbar-header">
    <button type="button" class="navbar-toggler hamburger hamburger-close navbar-toggler-left hided"
            data-toggle="menubar">
      <span class="sr-only">Toggle navigation</span>
      <span class="hamburger-bar"></span>
    </button>
    <button type="button" class="navbar-toggler collapsed" data-target="#site-navbar-collapse"
            data-toggle="collapse">
      <i class="icon wb-more-horizontal" aria-hidden="true"></i>
    </button>
    <div class="navbar-brand navbar-brand-center">
      <img class="navbar-brand-logo" src="{{ get_image(Auth::user()->BusinessLogo,'business_logo',Auth::user()->id,true) }}" title="{{ (Auth::user()->BusinessName) }}">
      <span class="navbar-brand-text hidden-xs-down">{{ (Auth::user()->BusinessName) }}</span>
    </div>
  </div>

  <div class="navbar-container container-fluid">
    <!-- Navbar Collapse -->
    <div class="collapse navbar-collapse navbar-collapse-toolbar" id="site-navbar-collapse">
      <!-- Navbar Toolbar -->
      <ul class="nav navbar-toolbar">
        <li class="nav-item hidden-float" id="toggleMenubar">
          <a class="nav-link" data-toggle="menubar" href="#" role="button">
            <i class="icon hamburger hamburger-arrow-left">
              <span class="sr-only">Toggle menubar</span>
              <span class="hamburger-bar"></span>
            </i>
          </a>
        </li>
        <li class="nav-item hidden-sm-down" id="toggleFullscreen">
          <a class="nav-link icon icon-fullscreen" data-toggle="fullscreen" href="#" role="button">
            <span class="sr-only">Toggle fullscreen</span>
          </a>
        </li>
        @if(get_merchant_id(false)->demo == 1)
        <li class="nav-item" style="background: #fffff5;">
          <a class="nav-link">
            <span class="red-600 text-center"><strong>DEMO</strong></span>
          </a>
        </li>
        @endif
      </ul>
      <!-- End Navbar Toolbar -->
      <!-- Navbar Toolbar Right -->
      <ul class="nav navbar-toolbar navbar-right navbar-toolbar-right" style="margin-right: 0px;">
        @php $user_guides = get_user_guide(); @endphp
        @if(!empty($user_guides))
        <li class="nav-item dropdown">
          <a class="nav-link navbar-avatar" id="profile" data-toggle="dropdown" href="#" aria-expanded="false"
             data-animation="scale-up" role="button"><i style="padding-top: 7px;" class="fa fa-headphones"></i>
          </a>
          <div class="dropdown-menu" role="menu">
            @foreach($user_guides as $key => $item)
            <a class="dropdown-item" target="_blank" href="{{$item['file']}}" role="menuitem">
              {{$item['name']}}
            </a>
            @if(count($user_guides) - 1 != $key)
              <div class="dropdown-divider" role="presentation"></div>@endif
            @endforeach
          </div>
        </li>
        @endif
        <li class="nav-item dropdown">
          <a class="nav-link" data-toggle="dropdown" href="javascript:void(0)" data-animation="scale-up"
             aria-expanded="false" role="button">
            @if(isset($languages))
              @foreach($languages as $language)
                @if(App::getLocale())
                  @if(strtolower(App::getLocale()) == strtolower($language->locale))
                    {{ $language->name }}
                   @endif
                  @else
                @if(!empty($default_language) && strtolower($default_language) == strtolower($language->locale))
                  {{ $language->name }}
                @endif
               @endif
              @endforeach
            @endif
          </a>
          <div class="dropdown-menu" role="menu">
            @if(isset($languages))
              @foreach($languages as $language)
                <a class="dropdown-item" href="{{ route('merchant.language',$language->locale) }}" role="menuitem">
                  {{ $language->name }}
                </a>
              @endforeach
            @endif
          </div>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link navbar-avatar" id="profile" data-toggle="dropdown" href="#" aria-expanded="false"
             data-animation="scale-up" role="button">
                <span class="avatar avatar-online">
                  <img src="{{ get_image(Auth::user()->BusinessLogo,'business_logo',Auth::user()->id,true) }}" alt="...">
                  <i></i>
                </span>
          </a>
          <div class="dropdown-menu" role="menu">
            <a class="dropdown-item" href="#" role="menuitem">
              <strong>{{Auth::user()->merchantFirstName." ".Auth::user()->merchantLastName}}</strong>
            </a>
            @if(Auth::user('merchant')->can('update_profile'))
              <div class="dropdown-divider" role="presentation"></div>
              <a class="dropdown-item" href="{{ route('merchant.profile') }}" role="menuitem">
                <i class="icon wb-user" aria-hidden="true"></i>@lang("$string_file.update_profile")</a>
            @endif
            <div class="dropdown-divider" role="presentation"></div>
            <a class="dropdown-item" href="{{ route('merchant.logout') }}" data-toggle="modal" data-target="#examplePositionTop" role="menuitem"><i class="icon wb-power" aria-hidden="true"></i> @lang("$string_file.logout")</a>
          </div>
        </li>
      </ul>
      <!-- End Navbar Toolbar Right -->
    </div>
    <!-- End Navbar Collapse -->

    <!-- Site Navbar Seach -->
    <div class="collapse navbar-search-overlap" id="site-navbar-search">
      <form role="search">
        <div class="form-group">
          <div class="input-search">
            <i class="input-search-icon wb-search" aria-hidden="true"></i>
            <input type="text" class="form-control" name="site-search" placeholder="Search...">
            <button type="button" class="input-search-close icon wb-close" data-target="#site-navbar-search"
                    data-toggle="collapse" aria-label="Close"></button>
          </div>
        </div>
      </form>
    </div>
    <!-- End Site Navbar Seach -->
  </div>
</nav>
<!-- Logout Modal-->
<div class="modal fade" id="examplePositionTop" tabindex="-1" role="dialog" aria-labelledby="examplePositionTops" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">@lang("$string_file.ready_to_leave")?</h5>
        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">×</span>
        </button>
      </div>
      <div class="modal-body">@lang("$string_file.end_current_session").</div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-dismiss="modal">@lang("$string_file.cancel")</button>
        <a class="btn btn-primary" href="{{ route('merchant.logout') }}">@lang("$string_file.logout")</a>
      </div>
    </div>
  </div>
</div>
