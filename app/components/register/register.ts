import {Component} from '@angular/core';
import {Router} from '@angular/router';
import {mergeMap} from 'rxjs';
import {NewUser} from "../../models/User";
import {ApiService} from "../../services/ApiService";
import {AuthService} from "../../services/AuthService";

@Component({
    templateUrl: 'register.html',
})
export class RegisterComponent {
    public submitting = false;
    public error: string;

    public newUser: NewUser = {
        fullName: '',
        email: '',
        password: '',
    };

    constructor(
        private api: ApiService,
        private authService: AuthService,
        private router: Router,
    ) {}

    register() {
        this.submitting = true;

        this.authService.createUser(this.newUser)
            .pipe(
                mergeMap(() => this.authService.logIn(this.newUser)),
                mergeMap(resp => {
                    this.api.setAuth(resp.token, true);
                    return this.api.getCurrentUser();
                }),
            )
            .subscribe({
                next: () => {
                    this.router.navigate(['/']);
                },
                error: error => {this.error = error;},
            })
            .add(() => {this.submitting = false;});
    }
}
