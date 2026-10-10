@extends('layouts.admin')

@section('title', 'Admin Profile')

@section('content')
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Admin Profile</h3>

            </div>
        </div>
    </div>

    <div class="nk-block">
        <div class="card card-bordered">
            <div class="card-inner">
                {{-- Start Navtab --}}
                <ul class="nav nav-tabs">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#tab_profile">
                            <em class="icon ni ni-user"></em><span>User Profile</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab_avatar">
                            <em class="icon ni ni-user-circle-fill"></em><span>Avatar</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab_password">
                            <em class="icon ni ni-lock-alt"></em><span>Change Password</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab_other">
                            <span>Other</span>
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane active" id="tab_profile">
                        <form>
                            <div class="form-group">
                                <label class="form-label" for="name">Name</label>
                                <div class="form-control-wrap">
                                    <input type="text" class="form-control" name="name" id="name" placeholder="Name">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="email">Email</label>
                                <div class="form-control-wrap">
                                    <input type="email" class="form-control" name="email" id="email" placeholder="Email">
                                </div>
                            </div>
                            <div class="form-control-wrap">
                                <label class="form-label" for="phone">Phone</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <button type="button" class="btn btn-outline-primary btn-dim dropdown-toggle" data-toggle="dropdown">
                                            <span>Phone</span>
                                            <em class="icon mx-n1 ni ni-chevron-down"></em>
                                        </button>
                                        <div class="dropdown-menu">
                                            <ul class="link-list-opt no-bdr">
                                                <li><a href="#">Action Settings</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <input type="telp" name="phone" id="phone" class="form-control" aria-label="Phone Number">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3">Update</button>
                        </form>
                    </div>

                    <div class="tab-pane" id="tab_avatar">
                        <form>
                            <div class="upload-zone">
                                <div class="dz-message" data-dz-message>
                                    <span class="dz-message-text">Drag and drop file</span>
                                    <span class="dz-message-or">or</span>
                                    <button class="btn btn-primary">SELECT</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="tab-pane" id="tab_password">
                        <form>
                            <div class="form-group">
                                <label class="form-label" for="current_password">Current Password</label>
                                <div class="form-control-wrap">
                                    <input type="password" class="form-control" name="current_password" id="current_password" placeholder="Current Passwors">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="password">Password</label>
                                <div class="form-control-wrap">
                                    <input type="password" class="form-control" name="password" id="password" placeholder="New Passwors">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="confirmation_password">Confirm Password</label>
                                <div class="form-control-wrap">
                                    <input type="password" class="form-control" name="confirmation_password" id="confirmation_password" placeholder="Confirmation Passwors">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3">Update</button>
                        </form>
                    </div>
                    <div class="tab-pane" id="tab_other">

                    </div>
                </div>
                {{-- End Navtab --}}

            </div>
        </div>
    </div>
@endsection
