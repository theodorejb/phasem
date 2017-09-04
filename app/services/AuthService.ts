import {Injectable} from '@angular/core';
import {LoginCredentials, NewUser} from '../models/User';
import {ApiService} from "./ApiService";

@Injectable()
export class AuthService {
    constructor (private api: ApiService) {}

    createUser(newUser: NewUser) {
        return this.api.requestBody('post', 'auth/user', newUser);
    }

    logIn(credentials: LoginCredentials) {
        return this.api.requestBody('post', 'auth/token', credentials)
            .map((resp) => {this.api.setAuth(resp.token);});
    }

    logOut() {
        return this.api.request('delete', 'auth/token')
            .map(() => {this.api.unsetCurrentUser();});
    }
}
