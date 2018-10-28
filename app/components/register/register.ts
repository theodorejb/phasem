import {Component} from '@angular/core';
import {Router} from '@angular/router';
import {mergeMap} from 'rxjs/operators';
import {NewUser} from "../../models/User";
import {ApiService} from "../../services/ApiService";
import {AuthService} from "../../services/AuthService";

@Component({
    templateUrl: 'register.html',
})
export class RegisterComponent {
    public submitting: boolean = false;
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
                mergeMap(() => this.api.getCurrentUser()),
            )
            .subscribe(
                () => {
                    this.router.navigate(['/']);
                },
                error => {this.error = error;},
            )
            .add(() => {this.submitting = false;});
    }
}
