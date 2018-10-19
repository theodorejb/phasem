import {Component, OnInit} from '@angular/core';
import {PasswordChange, User, UserEmail, UserProfile} from "../../../models/User";
import {ApiService} from "../../../services/ApiService";
import {UserService} from "../../../services/UserService";

@Component({
    templateUrl: 'account.html',
})
export class AccountComponent implements OnInit {
    public error: string;
    public user: User;
    public profile: UserProfile;
    public updatingProfile: boolean = false;
    public userEmail: UserEmail;
    public updatingEmail: boolean = false;
    public pwChange: PasswordChange;
    public changingPassword: boolean = false;

    constructor(
        private api: ApiService,
        private userService: UserService,
    ) {}

    ngOnInit() {
        this.api.getCurrentUser().subscribe(
            user => {
                this.user = user;

                this.profile = {
                    fullName: user.fullName,
                };

                this.userEmail = {
                    email: user.email,
                };

                this.pwChange = {
                    currentPassword: '',
                    newPassword: '',
                };
            },
            error => {this.error = error;},
        );
    }

    updateProfile() {
        this.updatingProfile = true;

        this.userService.updateProfile(this.profile)
            .subscribe(
                () => {
                    this.user.fullName = this.profile.fullName;
                    this.api.currentUser = this.user;
                    this.error = '';
                },
                error => {this.error = error;},
            )
            .add(() => {this.updatingProfile = false;});
    }

    updateEmail() {
        this.updatingEmail = true;

        this.userService.updateEmail(this.userEmail)
            .subscribe(
                () => {
                    this.user.email = this.userEmail.email;
                    this.api.currentUser = this.user;
                    this.error = '';
                },
                error => {this.error = error;},
            )
            .add(() => {this.updatingEmail = false;});
    }

    changePassword() {
        this.changingPassword = true;

        this.userService.changePassword(this.pwChange)
            .subscribe(
                () => {
                    this.pwChange.newPassword = this.pwChange.currentPassword = this.error = '';
                },
                error => {this.error = error;},
            )
            .add(() => {this.changingPassword = false;});
    }
}
