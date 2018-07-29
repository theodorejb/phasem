import {Component} from '@angular/core';
import {Router} from '@angular/router';
import {AuthService} from "../../services/AuthService";
import {LoginCredentials} from "../../models/User";
import {ApiService} from "../../services/ApiService";

@Component({
    templateUrl: 'login.html',
})
export class LoginComponent {
    public error: string;
    public submitting: boolean = false;

    public loginData: LoginCredentials = {
        email: '',
        password: '',
    };

    constructor(
        private authService: AuthService,
        private apiService: ApiService,
        private router: Router,
    ) {}

    signIn() {
        this.submitting = true;

        this.authService.logIn(this.loginData)
            .subscribe(
                () => {
                    let redirectUrl = this.apiService.getRedirectUrl();

                    if (!redirectUrl) {
                        redirectUrl = '/';
                    } else {
                        this.apiService.setRedirectUrl(null);
                    }

                    this.router.navigate([redirectUrl]);
                },
                error => {this.error = error;},
            )
            .add(() => {this.submitting = false;});
    }
}
