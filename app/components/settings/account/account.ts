import {Component, OnInit} from '@angular/core';
import {PasswordChange, User, UserEmail, UserProfile} from "../../../models/User";
import {ApiService} from "../../../services/ApiService";
import {UserService} from "../../../services/UserService";

@Component({
    templateUrl: 'account.html',
})
export class AccountComponent implements OnInit {
    public error: string;
    public success: string;
    public user: User;
    public profile: UserProfile;
    public updatingProfile = false;
    public userEmail: UserEmail;
    public updatingEmail = false;
    public pwChange: PasswordChange;
    public changingPassword = false;

    private errorHandler = (error: string) => {
        this.error = error;
        this.success = '';
    };

    constructor(
        private api: ApiService,
        private userService: UserService,
    ) {}

    ngOnInit() {
        this.api.getCurrentUser().subscribe({
            next: user => {
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
            error: this.errorHandler,
        });
    }

    updateProfile() {
        this.updatingProfile = true;

        this.userService.updateProfile(this.profile)
            .subscribe({
                next: () => {
                    this.user.fullName = this.profile.fullName;
                    this.api.currentUser = this.user;
                    this.error = '';
                    this.success = 'Successfully updated profile';
                },
                error: this.errorHandler,
            })
            .add(() => {this.updatingProfile = false;});
    }

    updateEmail() {
        this.updatingEmail = true;

        this.userService.updateEmail(this.userEmail)
            .subscribe({
                next: () => {
                    this.user.email = this.userEmail.email;
                    this.api.currentUser = this.user;
                    this.error = '';
                    this.success = 'Successfully updated email';
                },
                error: this.errorHandler,
            })
            .add(() => {this.updatingEmail = false;});
    }

    changePassword() {
        this.changingPassword = true;

        this.userService.changePassword(this.pwChange)
            .subscribe({
                next: () => {
                    this.pwChange.newPassword = this.pwChange.currentPassword = this.error = '';
                    this.success = 'Successfully changed password';
                },
                error: this.errorHandler,
            })
            .add(() => {this.changingPassword = false;});
    }
}
